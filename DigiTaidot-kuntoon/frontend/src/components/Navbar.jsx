import React, { useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { useAuth } from '../context/AuthContext';

const Navbar = () => {
  const { user, logout } = useAuth();
  const navigate = useNavigate();
  const [mobileMenuOpen, setMobileMenuOpen] = useState(false);

  const handleLogout = () => {
    logout();
    navigate('/');
    setMobileMenuOpen(false);
  };

  const toggleMobileMenu = () => {
    setMobileMenuOpen(!mobileMenuOpen);
  };

  return (
    <nav className="bg-white shadow-lg sticky top-0 z-50">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className="flex justify-between items-center h-16">
          {/* Logo */}
          <Link to="/" className="flex items-center space-x-3">
            <div className="w-10 h-10 bg-gradient-to-r from-primary-600 to-primary-800 rounded-lg flex items-center justify-center">
              <span className="text-white font-bold text-lg">DT</span>
            </div>
            <div>
              <h1 className="text-xl font-display font-bold gradient-text">
                DigiTaidot kuntoon!
              </h1>
              <p className="text-xs text-gray-500">Verkko-oppimisalusta</p>
            </div>
          </Link>

          {/* Desktop Navigation */}
          <div className="hidden md:flex items-center space-x-8">
            <Link 
              to="/" 
              className="text-gray-700 hover:text-primary-600 px-3 py-2 text-sm font-medium transition-colors duration-200"
            >
              Etusivu
            </Link>
            
            {user && (
              <>
                {user.role === 'teacher' || user.role === 'admin' ? (
                  <Link 
                    to="/teacher" 
                    className="text-gray-700 hover:text-primary-600 px-3 py-2 text-sm font-medium transition-colors duration-200"
                  >
                    Opettajan hallinta
                  </Link>
                ) : (
                  <>
                    <Link 
                      to="/courses" 
                      className="text-gray-700 hover:text-primary-600 px-3 py-2 text-sm font-medium transition-colors duration-200"
                    >
                      Kurssit
                    </Link>
                    <Link 
                      to="/dashboard" 
                      className="text-gray-700 hover:text-primary-600 px-3 py-2 text-sm font-medium transition-colors duration-200"
                    >
                      Oma sivu
                    </Link>
                  </>
                )}
              </>
            )}
            
            {user ? (
              <div className="flex items-center space-x-4">
                {/* Subscription status */}
                {user.subscriptionActive ? (
                  <span className="bg-success-100 text-success-800 text-xs font-semibold px-2 py-1 rounded-full">
                    💎 Tilaus aktiivinen
                  </span>
                ) : (
                  <Link 
                    to="/subscription"
                    className="bg-primary-600 text-white text-xs font-semibold px-3 py-1 rounded-full hover:bg-primary-700 transition-colors duration-200"
                  >
                    Tilaa 5,99€/kk
                  </Link>
                )}
                
                {/* User menu */}
                <div className="relative group">
                  <button className="flex items-center space-x-2 text-gray-700 hover:text-primary-600 transition-colors duration-200">
                    <div className="w-8 h-8 bg-primary-100 rounded-full flex items-center justify-center">
                      <span className="text-primary-600 font-semibold text-sm">
                        {user.firstName?.charAt(0)?.toUpperCase()}
                      </span>
                    </div>
                    <span className="text-sm font-medium">{user.firstName}</span>
                    <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M19 9l-7 7-7-7" />
                    </svg>
                  </button>
                  
                  {/* Dropdown menu */}
                  <div className="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg border border-gray-200 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200">
                    <div className="py-1">
                      <Link 
                        to="/profile" 
                        className="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 transition-colors duration-200"
                      >
                        Profiili
                      </Link>
                      <Link 
                        to="/dashboard" 
                        className="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 transition-colors duration-200"
                      >
                        Oma sivu
                      </Link>
                      {!user.subscriptionActive && (
                        <Link 
                          to="/subscription" 
                          className="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 transition-colors duration-200"
                        >
                          Tilaus
                        </Link>
                      )}
                      <hr className="my-1" />
                      <button 
                        onClick={handleLogout}
                        className="block w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50 transition-colors duration-200"
                      >
                        Kirjaudu ulos
                      </button>
                    </div>
                  </div>
                </div>
              </div>
            ) : (
              <div className="flex items-center space-x-4">
                <Link 
                  to="/login" 
                  className="text-gray-700 hover:text-primary-600 px-3 py-2 text-sm font-medium transition-colors duration-200"
                >
                  Kirjaudu
                </Link>
                <Link 
                  to="/register" 
                  className="btn-primary text-sm"
                >
                  Rekisteröidy
                </Link>
              </div>
            )}
          </div>

          {/* Mobile menu button */}
          <div className="md:hidden">
            <button 
              onClick={toggleMobileMenu}
              className="text-gray-700 hover:text-primary-600 focus:outline-none focus:text-primary-600"
            >
              <svg className="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M4 6h16M4 12h16M4 18h16" />
              </svg>
            </button>
          </div>
        </div>

        {/* Mobile menu */}
        {mobileMenuOpen && (
          <div className="md:hidden">
            <div className="px-2 pt-2 pb-3 space-y-1 sm:px-3 bg-gray-50 border-t border-gray-200">
              <Link 
                to="/" 
                onClick={() => setMobileMenuOpen(false)}
                className="text-gray-700 hover:text-primary-600 block px-3 py-2 text-base font-medium"
              >
                Etusivu
              </Link>
              
              {user && (
                <>
                  {user.role === 'teacher' || user.role === 'admin' ? (
                    <Link 
                      to="/teacher" 
                      onClick={() => setMobileMenuOpen(false)}
                      className="text-gray-700 hover:text-primary-600 block px-3 py-2 text-base font-medium"
                    >
                      Opettajan hallinta
                    </Link>
                  ) : (
                    <>
                      <Link 
                        to="/courses" 
                        onClick={() => setMobileMenuOpen(false)}
                        className="text-gray-700 hover:text-primary-600 block px-3 py-2 text-base font-medium"
                      >
                        Kurssit
                      </Link>
                      <Link 
                        to="/dashboard" 
                        onClick={() => setMobileMenuOpen(false)}
                        className="text-gray-700 hover:text-primary-600 block px-3 py-2 text-base font-medium"
                      >
                        Oma sivu
                      </Link>
                    </>
                  )}
                  
                  <Link 
                    to="/profile" 
                    onClick={() => setMobileMenuOpen(false)}
                    className="text-gray-700 hover:text-primary-600 block px-3 py-2 text-base font-medium"
                  >
                    Profiili
                  </Link>
                  
                  {!user.subscriptionActive && (
                    <Link 
                      to="/subscription" 
                      onClick={() => setMobileMenuOpen(false)}
                      className="text-gray-700 hover:text-primary-600 block px-3 py-2 text-base font-medium"
                    >
                      Tilaus
                    </Link>
                  )}
                  
                  <button 
                    onClick={handleLogout}
                    className="text-red-600 hover:text-red-700 block w-full text-left px-3 py-2 text-base font-medium"
                  >
                    Kirjaudu ulos
                  </button>
                </>
              )}
              
              {!user && (
                <>
                  <Link 
                    to="/login" 
                    onClick={() => setMobileMenuOpen(false)}
                    className="text-gray-700 hover:text-primary-600 block px-3 py-2 text-base font-medium"
                  >
                    Kirjaudu
                  </Link>
                  <Link 
                    to="/register" 
                    onClick={() => setMobileMenuOpen(false)}
                    className="btn-primary block text-center mx-3"
                  >
                    Rekisteröidy
                  </Link>
                </>
              )}
            </div>
          </div>
        )}
      </div>
    </nav>
  );
};

export default Navbar;
