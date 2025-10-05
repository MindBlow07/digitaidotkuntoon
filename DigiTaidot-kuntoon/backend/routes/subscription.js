const express = require('express');
const stripe = require('stripe')(process.env.STRIPE_SECRET_KEY);
const { Pool } = require('pg');

const router = express.Router();
const pool = new Pool({
  host: process.env.DB_HOST,
  port: process.env.DB_PORT,
  database: process.env.DB_NAME,
  user: process.env.DB_USER,
  password: process.env.DB_PASSWORD,
});

// Token validointi middleware
const authenticateToken = (req, res, next) => {
  const authHeader = req.headers['authorization'];
  const token = authHeader && authHeader.split(' ')[1];

  if (!token) {
    return res.status(401).json({ error: 'Pääsy kielletty - token puuttuu' });
  }

  const jwt = require('jsonwebtoken');
  jwt.verify(token, process.env.JWT_SECRET, (err, user) => {
    if (err) {
      return res.status(403).json({ error: 'Pääsy kielletty - virheellinen token' });
    }
    req.user = user;
    next();
  });
};

// Luo Stripe Checkout sessio
router.post('/create-checkout-session', authenticateToken, async (req, res) => {
  try {
    const priceId = 'price_digitaidot_monthly'; // Stripe Price ID 5.99€/kk
    
    // Luo tai hae Stripe asiakas
    let customer;
    const userResult = await pool.query(
      'SELECT stripe_customer_id FROM users WHERE id = $1',
      [req.user.userId]
    );

    if (userResult.rows[0]?.stripe_customer_id) {
      customer = userResult.rows[0].stripe_customer_id;
    } else {
      const userInfo = await pool.query(
        'SELECT email, first_name, last_name FROM users WHERE id = $1',
        [req.user.userId]
      );
      
      const stripeCustomer = await stripe.customers.create({
        email: userInfo.rows[0].email,
        name: `${userInfo.rows[0].first_name} ${userInfo.rows[0].last_name}`,
        metadata: {
          userId: req.user.userId.toString()
        }
      });

      customer = stripeCustomer.id;
      
      // Tallenna Stripe customer ID tietokantaan
      await pool.query(
        'UPDATE users SET stripe_customer_id = $1 WHERE id = $2',
        [customer, req.user.userId]
      );
    }

    // Luo Checkout sessio
    const session = await stripe.checkout.sessions.create({
      customer: customer,
      payment_method_types: ['card'],
      line_items: [
        {
          price: priceId,
          quantity: 1,
        },
      ],
      mode: 'subscription',
      success_url: `${process.env.FRONTEND_URL}/subscription/success?session_id={CHECKOUT_SESSION_ID}`,
      cancel_url: `${process.env.FRONTEND_URL}/subscription/cancel`,
      metadata: {
        userId: req.user.userId.toString()
      }
    });

    res.json({ sessionId: session.id, url: session.url });

  } catch (error) {
    console.error('Stripe checkout virhe:', error);
    res.status(500).json({ error: 'Tilauksen luominen epäonnistui' });
  }
});

// Stripe webhook
router.post('/webhook', express.raw({type: 'application/json'}), async (req, res) => {
  const sig = req.headers['stripe-signature'];
  let event;

  try {
    event = stripe.webhooks.constructEvent(req.body, sig, process.env.STRIPE_WEBHOOK_SECRET);
  } catch (err) {
    console.error('Webhook signature verification failed:', err.message);
    return res.status(400).send(`Webhook Error: ${err.message}`);
  }

  try {
    switch (event.type) {
      case 'checkout.session.completed':
        await handleCheckoutSessionCompleted(event.data.object);
        break;
      
      case 'customer.subscription.created':
      case 'customer.subscription.updated':
        await handleSubscriptionUpdated(event.data.object);
        break;
      
      case 'customer.subscription.deleted':
        await handleSubscriptionDeleted(event.data.object);
        break;
      
      case 'invoice.payment_succeeded':
        await handlePaymentSucceeded(event.data.object);
        break;
      
      case 'invoice.payment_failed':
        await handlePaymentFailed(event.data.object);
        break;
      
      default:
        console.log(`Unhandled event type ${event.type}`);
    }

    res.json({ received: true });

  } catch (error) {
    console.error('Webhook processing error:', error);
    res.status(500).json({ error: 'Webhook käsittely epäonnistui' });
  }
});

// Tilaus aktivoitu
async function handleCheckoutSessionCompleted(session) {
  const userId = session.metadata.userId;
  
  await pool.query(
    'UPDATE users SET subscription_active = true WHERE id = $1',
    [userId]
  );

  // Lisää tilaushistoriaan
  await pool.query(
    'INSERT INTO subscriptions (user_id, stripe_subscription_id, status, current_period_start, current_period_end) VALUES ($1, $2, $3, $4, $5)',
    [userId, session.subscription, 'active', new Date(), new Date(Date.now() + 30 * 24 * 60 * 60 * 1000)]
  );

  console.log(`✅ Tilaus aktivoitu käyttäjälle: ${userId}`);
}

// Tilaus päivitetty
async function handleSubscriptionUpdated(subscription) {
  const customer = await stripe.customers.retrieve(subscription.customer);
  const userId = customer.metadata.userId;

  const isActive = subscription.status === 'active';
  const currentPeriodEnd = new Date(subscription.current_period_end * 1000);

  await pool.query(
    'UPDATE users SET subscription_active = $1, subscription_end_date = $2 WHERE id = $3',
    [isActive, currentPeriodEnd, userId]
  );

  // Päivitä tilaushistoria
  await pool.query(
    'UPDATE subscriptions SET status = $1, current_period_start = $2, current_period_end = $3 WHERE stripe_subscription_id = $4',
    [subscription.status, new Date(subscription.current_period_start * 1000), currentPeriodEnd, subscription.id]
  );

  console.log(`📝 Tilaus päivitetty käyttäjälle: ${userId}, tila: ${subscription.status}`);
}

// Tilaus peruttu
async function handleSubscriptionDeleted(subscription) {
  const customer = await stripe.customers.retrieve(subscription.customer);
  const userId = customer.metadata.userId;

  await pool.query(
    'UPDATE users SET subscription_active = false WHERE id = $1',
    [userId]
  );

  await pool.query(
    'UPDATE subscriptions SET status = $1 WHERE stripe_subscription_id = $2',
    ['canceled', subscription.id]
  );

  console.log(`❌ Tilaus peruttu käyttäjälle: ${userId}`);
}

// Maksu onnistui
async function handlePaymentSucceeded(invoice) {
  const subscription = await stripe.subscriptions.retrieve(invoice.subscription);
  const customer = await stripe.customers.retrieve(subscription.customer);
  const userId = customer.metadata.userId;

  await pool.query(
    'UPDATE users SET subscription_active = true, subscription_end_date = $1 WHERE id = $2',
    [new Date(subscription.current_period_end * 1000), userId]
  );

  console.log(`💰 Maksu onnistui käyttäjälle: ${userId}`);
}

// Maksu epäonnistui
async function handlePaymentFailed(invoice) {
  const subscription = await stripe.subscriptions.retrieve(invoice.subscription);
  const customer = await stripe.customers.retrieve(subscription.customer);
  const userId = customer.metadata.userId;

  console.log(`💳 Maksu epäonnistui käyttäjälle: ${userId}`);
  // Stripe hoitaa automaattisesti tilauksen perumisen maksuvirheiden jälkeen
}

// Hae käyttäjän tilauksen tila
router.get('/status', authenticateToken, async (req, res) => {
  try {
    const result = await pool.query(
      'SELECT subscription_active, subscription_end_date, stripe_customer_id FROM users WHERE id = $1',
      [req.user.userId]
    );

    if (result.rows.length === 0) {
      return res.status(404).json({ error: 'Käyttäjää ei löytynyt' });
    }

    const user = result.rows[0];
    const subscriptionActive = user.subscription_active && 
      (!user.subscription_end_date || new Date(user.subscription_end_date) > new Date());

    res.json({
      active: subscriptionActive,
      endDate: user.subscription_end_date,
      hasStripeCustomer: !!user.stripe_customer_id
    });

  } catch (error) {
    console.error('Tilauksen tilan hakuvirhe:', error);
    res.status(500).json({ error: 'Tilauksen tilan haku epäonnistui' });
  }
});

// Peru tilaus
router.post('/cancel', authenticateToken, async (req, res) => {
  try {
    const result = await pool.query(
      'SELECT stripe_customer_id FROM users WHERE id = $1',
      [req.user.userId]
    );

    if (result.rows.length === 0 || !result.rows[0].stripe_customer_id) {
      return res.status(404).json({ error: 'Stripe asiakasta ei löytynyt' });
    }

    // Hae aktiivinen tilaus
    const subscriptions = await stripe.subscriptions.list({
      customer: result.rows[0].stripe_customer_id,
      status: 'active'
    });

    if (subscriptions.data.length === 0) {
      return res.status(404).json({ error: 'Aktiivista tilausta ei löytynyt' });
    }

    // Peru tilaus
    await stripe.subscriptions.update(subscriptions.data[0].id, {
      cancel_at_period_end: true
    });

    res.json({ message: 'Tilaus peruttu. Se päättyy nykyisen jakson lopussa.' });

  } catch (error) {
    console.error('Tilauksen perumisvirhe:', error);
    res.status(500).json({ error: 'Tilauksen peruminen epäonnistui' });
  }
});

module.exports = router;
