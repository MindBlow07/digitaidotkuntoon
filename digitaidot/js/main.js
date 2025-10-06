// DigiTaidot Kuntoon! - JavaScript-toiminnot

document.addEventListener('DOMContentLoaded', function() {
    // Alusta sivun toiminnallisuudet
    initializePage();
});

function initializePage() {
    // Auto-dismiss alertit
    autoDismissAlerts();
    
    // Paranna lomakkeiden käyttökokemusta
    enhanceForms();
    
    // Lisää animaatiot
    addAnimations();
    
    // Paranna taulukoiden käyttökokemusta
    enhanceTables();
    
    // PayPal-nappulan alustus (jos sivulla on)
    initializePayPalButton();
}

// Auto-dismiss alertit 5 sekunnin kuluttua
function autoDismissAlerts() {
    const alerts = document.querySelectorAll('.alert-dismissible');
    alerts.forEach(function(alert) {
        setTimeout(function() {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }, 5000);
    });
}

// Paranna lomakkeiden käyttökokemusta
function enhanceForms() {
    // Lisää loading-tila painikkeisiin
    const forms = document.querySelectorAll('form');
    forms.forEach(function(form) {
        form.addEventListener('submit', function(e) {
            const submitBtn = form.querySelector('button[type="submit"]');
            if (submitBtn && !submitBtn.disabled) {
                const originalText = submitBtn.innerHTML;
                submitBtn.innerHTML = '<span class="spinner"></span> Käsitellään...';
                submitBtn.disabled = true;
                
                // Jos lomake ei lähetetä (esim. validointivirhe), palauta painike
                setTimeout(function() {
                    if (submitBtn.disabled) {
                        submitBtn.innerHTML = originalText;
                        submitBtn.disabled = false;
                    }
                }, 100);
            }
        });
    });
    
    // Paranna salasanan vahvuuden näyttöä
    const passwordInputs = document.querySelectorAll('input[type="password"]');
    passwordInputs.forEach(function(input) {
        if (input.name === 'password') {
            input.addEventListener('input', function() {
                const strength = calculatePasswordStrength(this.value);
                showPasswordStrength(this, strength);
            });
        }
    });
    
    // Salasanan vahvistus
    const confirmPasswordInputs = document.querySelectorAll('input[name="confirm_password"]');
    confirmPasswordInputs.forEach(function(input) {
        const passwordInput = input.form.querySelector('input[name="password"]');
        if (passwordInput) {
            input.addEventListener('input', function() {
                validatePasswordMatch(passwordInput, this);
            });
        }
    });
}

// Laske salasanan vahvuus
function calculatePasswordStrength(password) {
    let score = 0;
    
    if (password.length >= 6) score++;
    if (password.length >= 8) score++;
    if (/[a-z]/.test(password)) score++;
    if (/[A-Z]/.test(password)) score++;
    if (/[0-9]/.test(password)) score++;
    if (/[^A-Za-z0-9]/.test(password)) score++;
    
    return Math.min(score, 5);
}

// Näytä salasanan vahvuus
function showPasswordStrength(input, strength) {
    let strengthIndicator = input.parentNode.querySelector('.password-strength');
    
    if (!strengthIndicator) {
        strengthIndicator = document.createElement('div');
        strengthIndicator.className = 'password-strength mt-1';
        input.parentNode.appendChild(strengthIndicator);
    }
    
    const strengthTexts = ['Erittäin heikko', 'Heikko', 'Kohtalainen', 'Hyvä', 'Erittäin hyvä'];
    const strengthColors = ['danger', 'warning', 'info', 'success', 'success'];
    
    if (input.value.length > 0) {
        strengthIndicator.innerHTML = `
            <div class="progress" style="height: 4px;">
                <div class="progress-bar bg-${strengthColors[strength - 1] || 'secondary'}" 
                     style="width: ${(strength / 5) * 100}%"></div>
            </div>
            <small class="text-${strengthColors[strength - 1] || 'secondary'}">
                ${strengthTexts[strength - 1] || 'Heikko'}
            </small>
        `;
    } else {
        strengthIndicator.innerHTML = '';
    }
}

// Tarkista salasanojen täsmääminen
function validatePasswordMatch(passwordInput, confirmInput) {
    const matchIndicator = confirmInput.parentNode.querySelector('.password-match');
    
    if (!matchIndicator) {
        const indicator = document.createElement('div');
        indicator.className = 'password-match mt-1';
        confirmInput.parentNode.appendChild(indicator);
    }
    
    if (confirmInput.value.length > 0) {
        if (passwordInput.value === confirmInput.value) {
            matchIndicator.innerHTML = '<small class="text-success"><i class="bi bi-check-circle"></i> Salasanat täsmäävät</small>';
            confirmInput.classList.remove('is-invalid');
            confirmInput.classList.add('is-valid');
        } else {
            matchIndicator.innerHTML = '<small class="text-danger"><i class="bi bi-x-circle"></i> Salasanat eivät täsmää</small>';
            confirmInput.classList.remove('is-valid');
            confirmInput.classList.add('is-invalid');
        }
    } else {
        matchIndicator.innerHTML = '';
        confirmInput.classList.remove('is-valid', 'is-invalid');
    }
}

// Lisää animaatiot elementeille
function addAnimations() {
    // Fade-in animaatio korteille
    const cards = document.querySelectorAll('.card');
    const observer = new IntersectionObserver(function(entries) {
        entries.forEach(function(entry) {
            if (entry.isIntersecting) {
                entry.target.classList.add('fade-in');
                observer.unobserve(entry.target);
            }
        });
    }, { threshold: 0.1 });
    
    cards.forEach(function(card) {
        observer.observe(card);
    });
    
    // Hover-animaatiot
    const hoverElements = document.querySelectorAll('.course-card, .quiz-card, .btn');
    hoverElements.forEach(function(element) {
        element.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-2px)';
        });
        
        element.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
        });
    });
}

// Paranna taulukoiden käyttökokemusta
function enhanceTables() {
    const tables = document.querySelectorAll('table');
    
    tables.forEach(function(table) {
        // Lisää zebra-striping jos ei ole
        if (!table.classList.contains('table-striped')) {
            table.classList.add('table-hover');
        }
        
        // Lisää sortointi otsikoille (jos halutaan)
        const headers = table.querySelectorAll('th[data-sortable]');
        headers.forEach(function(header) {
            header.style.cursor = 'pointer';
            header.addEventListener('click', function() {
                sortTable(table, header.cellIndex);
            });
        });
    });
}

// Taulukon lajittelu
function sortTable(table, columnIndex) {
    const tbody = table.querySelector('tbody');
    const rows = Array.from(tbody.querySelectorAll('tr'));
    
    rows.sort(function(a, b) {
        const aText = a.cells[columnIndex].textContent.trim();
        const bText = b.cells[columnIndex].textContent.trim();
        
        // Yritä numeerinen vertailu ensin
        const aNum = parseFloat(aText);
        const bNum = parseFloat(bText);
        
        if (!isNaN(aNum) && !isNaN(bNum)) {
            return aNum - bNum;
        }
        
        // Muuten teksti-vertailu
        return aText.localeCompare(bText);
    });
    
    // Tyhjennä ja lisää uudelleen järjestettynä
    tbody.innerHTML = '';
    rows.forEach(function(row) {
        tbody.appendChild(row);
    });
}

// PayPal-nappulan alustus
function initializePayPalButton() {
    const paypalContainer = document.getElementById('paypal-button-container');
    
    if (paypalContainer && typeof paypal !== 'undefined') {
        // PayPal-nappula on jo alustettu header.php:ssä
        console.log('PayPal-nappula alustettu');
    }
}

// Utility-funktiot
function showToast(message, type = 'info') {
    // Luo toast-ilmoitus
    const toastContainer = document.querySelector('.toast-container') || createToastContainer();
    
    const toast = document.createElement('div');
    toast.className = `toast align-items-center text-white bg-${type} border-0`;
    toast.setAttribute('role', 'alert');
    toast.innerHTML = `
        <div class="d-flex">
            <div class="toast-body">${message}</div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    `;
    
    toastContainer.appendChild(toast);
    
    const bsToast = new bootstrap.Toast(toast);
    bsToast.show();
    
    // Poista toast DOM:sta sen jälkeen kun se on piilotettu
    toast.addEventListener('hidden.bs.toast', function() {
        toast.remove();
    });
}

function createToastContainer() {
    const container = document.createElement('div');
    container.className = 'toast-container position-fixed top-0 end-0 p-3';
    container.style.zIndex = '9999';
    document.body.appendChild(container);
    return container;
}

// Confirmation dialog
function confirmAction(message, callback) {
    if (confirm(message)) {
        callback();
    }
}

// Loading overlay
function showLoading(container = document.body) {
    const overlay = document.createElement('div');
    overlay.className = 'loading-overlay';
    overlay.innerHTML = `
        <div class="loading-content">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Ladataan...</span>
            </div>
            <p class="mt-2">Ladataan...</p>
        </div>
    `;
    
    overlay.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        display: flex;
        justify-content: center;
        align-items: center;
        z-index: 9999;
    `;
    
    container.appendChild(overlay);
    return overlay;
}

function hideLoading(overlay) {
    if (overlay && overlay.parentNode) {
        overlay.parentNode.removeChild(overlay);
    }
}

// Form validation helpers
function validateEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
}

function validateRequired(input) {
    return input.value.trim().length > 0;
}

function showFieldError(input, message) {
    input.classList.add('is-invalid');
    
    let feedback = input.parentNode.querySelector('.invalid-feedback');
    if (!feedback) {
        feedback = document.createElement('div');
        feedback.className = 'invalid-feedback';
        input.parentNode.appendChild(feedback);
    }
    
    feedback.textContent = message;
}

function clearFieldError(input) {
    input.classList.remove('is-invalid');
    const feedback = input.parentNode.querySelector('.invalid-feedback');
    if (feedback) {
        feedback.remove();
    }
}

// Smooth scroll
function smoothScrollTo(target, offset = 0) {
    const element = document.querySelector(target);
    if (element) {
        const elementPosition = element.offsetTop - offset;
        window.scrollTo({
            top: elementPosition,
            behavior: 'smooth'
        });
    }
}

// Copy to clipboard
function copyToClipboard(text) {
    if (navigator.clipboard) {
        navigator.clipboard.writeText(text).then(function() {
            showToast('Kopioitu leikepöydälle!', 'success');
        });
    } else {
        // Fallback vanhemmille selaimille
        const textArea = document.createElement('textarea');
        textArea.value = text;
        document.body.appendChild(textArea);
        textArea.select();
        document.execCommand('copy');
        document.body.removeChild(textArea);
        showToast('Kopioitu leikepöydälle!', 'success');
    }
}

// Export functions to global scope
window.DigiTaidot = {
    showToast,
    confirmAction,
    showLoading,
    hideLoading,
    validateEmail,
    validateRequired,
    showFieldError,
    clearFieldError,
    smoothScrollTo,
    copyToClipboard
};
