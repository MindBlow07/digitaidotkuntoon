import React from 'react';
import { Link } from 'react-router-dom';

const Footer = () => {
  return (
    <footer className="bg-gray-900 text-white py-12 mt-16">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className="grid grid-cols-1 md:grid-cols-4 gap-8">
          {/* Brand */}
          <div className="col-span-1 md:col-span-2">
            <div className="flex items-center space-x-3 mb-4">
              <div className="w-10 h-10 bg-gradient-to-r from-primary-600 to-primary-800 rounded-lg flex items-center justify-center">
                <span className="text-white font-bold text-lg">DT</span>
              </div>
              <div>
                <h3 className="text-xl font-display font-bold">DigiTaidot kuntoon!</h3>
                <p className="text-sm text-gray-400">Verkko-oppimisalusta</p>
              </div>
            </div>
            <p className="text-gray-300 mb-6 max-w-md">
              Moderni verkko-oppimisalusta tietoturvaan, ohjelmointiin ja digi-aiheisiin. 
              Paranna digitaalisia taitojasi 5,99€/kk tilausmallilla.
            </p>
            <div className="flex space-x-4">
              <div className="bg-primary-600 text-white px-3 py-1 rounded-full text-sm font-semibold">
                💎 Premium sisältö
              </div>
              <div className="bg-success-600 text-white px-3 py-1 rounded-full text-sm font-semibold">
                🎓 Sertifikaatit
              </div>
            </div>
          </div>

          {/* Quick Links */}
          <div>
            <h4 className="text-lg font-semibold mb-4">Pikalinkit</h4>
            <ul className="space-y-2">
              <li>
                <Link to="/" className="text-gray-300 hover:text-white transition-colors duration-200">
                  Etusivu
                </Link>
              </li>
              <li>
                <Link to="/courses" className="text-gray-300 hover:text-white transition-colors duration-200">
                  Kurssit
                </Link>
              </li>
              <li>
                <Link to="/dashboard" className="text-gray-300 hover:text-white transition-colors duration-200">
                  Oma sivu
                </Link>
              </li>
              <li>
                <Link to="/subscription" className="text-gray-300 hover:text-white transition-colors duration-200">
                  Tilaus
                </Link>
              </li>
            </ul>
          </div>

          {/* Categories */}
          <div>
            <h4 className="text-lg font-semibold mb-4">Kurssikategoriat</h4>
            <ul className="space-y-2">
              <li>
                <span className="text-gray-300 hover:text-white transition-colors duration-200 cursor-pointer">
                  🏠 Tietoturva kodissa
                </span>
              </li>
              <li>
                <span className="text-gray-300 hover:text-white transition-colors duration-200 cursor-pointer">
                  🏢 Tietoturva työpaikalla
                </span>
              </li>
              <li>
                <span className="text-gray-300 hover:text-white transition-colors duration-200 cursor-pointer">
                  💻 Ohjelmointi
                </span>
              </li>
              <li>
                <span className="text-gray-300 hover:text-white transition-colors duration-200 cursor-pointer">
                  📱 Digi-aiheet
                </span>
              </li>
            </ul>
          </div>
        </div>

        {/* Bottom section */}
        <div className="border-t border-gray-800 mt-8 pt-8">
          <div className="flex flex-col md:flex-row justify-between items-center">
            <div className="text-gray-400 text-sm mb-4 md:mb-0">
              © 2025 DigiTaidot kuntoon! Kaikki oikeudet pidätetään.
            </div>
            
            <div className="flex space-x-6 text-sm">
              <a href="#" className="text-gray-400 hover:text-white transition-colors duration-200">
                Tietosuojakäytäntö
              </a>
              <a href="#" className="text-gray-400 hover:text-white transition-colors duration-200">
                Käyttöehdot
              </a>
              <a href="#" className="text-gray-400 hover:text-white transition-colors duration-200">
                Ota yhteyttä
              </a>
            </div>
          </div>
          
          {/* Pricing info */}
          <div className="mt-4 text-center">
            <p className="text-gray-400 text-sm">
              💰 Tilaushinta: <span className="text-white font-semibold">5,99€/kk</span> • 
              🔄 Peru milloin tahansa • 
              🎯 Kaikki kurssit sisältyvät
            </p>
          </div>
        </div>
      </div>
    </footer>
  );
};

export default Footer;
