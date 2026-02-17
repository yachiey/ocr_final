import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    darkMode: 'class',

    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.vue',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                // Primary Brand Color (Dark Blue #2065ac)
                primary: {
                    50: '#f0f6fc',
                    100: '#e1ecf8',
                    200: '#c3daf3',
                    300: '#96bfe9',
                    400: '#629edc',
                    500: '#3d81ce',
                    600: '#2065ac', // Base brand color
                    700: '#1a518c',
                    800: '#174474',
                    900: '#163960',
                    950: '#0f243e',
                },
                // Secondary Brand Colors
                brand: {
                    red: '#e93c22',
                    orange: '#f49111',
                    'dark-orange': '#eb6016',
                    'light-blue': '#5fa7d6',
                    'dark-blue': '#2065ac',
                }
            },
            animation: {
                'fade-in-up': 'fadeInUp 0.8s ease-out forwards',
                'blob': 'blob 7s infinite',
            },
            keyframes: {
                fadeInUp: {
                    '0%': { opacity: '0', transform: 'translateY(20px)' },
                    '100%': { opacity: '1', transform: 'translateY(0)' },
                },
                blob: {
                    '0%': { transform: 'translate(0px, 0px) scale(1)' },
                    '33%': { transform: 'translate(30px, -50px) scale(1.1)' },
                    '66%': { transform: 'translate(-20px, 20px) scale(0.9)' },
                    '100%': { transform: 'translate(0px, 0px) scale(1)' },
                }
            },
        },
    },

    plugins: [forms],
};
