module.exports = {
  content: [
    // Scan ALL PHP and PVue files recursively
    "./**/*.php",
    "./**/*.pvue",
    "./**/*.html",
    
    // Be more specific about your component structure
    "./components/**/*.pvue",
    "./components/**/*.php",
    
    // Include your main entry points
    "./index.php",
    "./*.php",
    
    // JavaScript files if you use Tailwind classes in JS
    "./assets/**/*.js"
  ],
  theme: {
    extend: {
      colors: {
        // PHPue Matrix
        'stone': {
            50: '#FAF8F5',
            100: '#F4EFEA',
            200: '#EADFD5',
            300: '#D5C3B3',
            400: '#BFA894',
            500: '#A38872',
            600: '#856A54',
            700: '#67503F',
            800: '#4D3A2D',
            900: '#30241C',
        },
        
        'gold': '#C5A880',
        // Your agency colors
        'pine': '#034748',
        'flame': '#E4572E',
        'yellow': '#FFC914',
        'cambridge': '#7B9E87',
        'snow': '#FCF7F8',
        
        // Gradient variants
        'pine-light': '#055d5e',
        'pine-dark': '#023535',
        'flame-light': '#f97316',
        'flame-dark': '#c2410c',
      },
      fontFamily: {
        'inter': ['Inter', 'sans-serif'],
      },
      backgroundImage: {
        'hero-gradient': 'linear-gradient(135deg, #034748 0%, #055d5e 100%)',
        'agency-gradient': 'linear-gradient(135deg, #034748 0%, #055d5e 100%)',
      },
      backdropBlur: {
        'xs': '2px',
      },
      animation: {
        'fade-in': 'fadeIn 0.8s ease-out',
        'slide-down': 'slideDown 0.3s ease',
      },
      keyframes: {
        fadeIn: {
          '0%': { opacity: '0', transform: 'translateY(20px)' },
          '100%': { opacity: '1', transform: 'translateY(0)' },
        },
        slideDown: {
          '0%': { opacity: '0', transform: 'translateY(-10px)' },
          '100%': { opacity: '1', transform: 'translateY(0)' },
        },
      },
    },
  },
  plugins: [],
}