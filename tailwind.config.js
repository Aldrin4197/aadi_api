import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.vue',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Quicksand', 'Figtree', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                aadi: {
                    // Primary Teal
                    teal: {
                        DEFAULT: '#1FA6A0',
                        50: '#E3F6F4',
                        100: '#C4ECEA',
                        200: '#8DD9D4',
                        300: '#56C6BF',
                        400: '#1FA6A0',
                        500: '#1A8F8A',
                        600: '#157873',
                        700: '#0F5F5C',
                        800: '#0B3A38',
                        900: '#062827',
                    },
                    // Accent Orange
                    orange: {
                        DEFAULT: '#F59E42',
                        50: '#FEF5EB',
                        100: '#FCE6CE',
                        200: '#F9CE9C',
                        300: '#F7B66B',
                        400: '#F59E42',
                        500: '#D9822B',
                        600: '#BD6615',
                        700: '#8F4D10',
                        800: '#61340B',
                        900: '#331B06',
                    },
                },
            },
        },
    },

    plugins: [forms],
};
