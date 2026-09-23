import defaultTheme from 'tailwindcss/defaultTheme';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/**/*.blade.php',
        './resources/**/*.js',
        './resources/**/*.vue',
    ],
    theme: {
        extend: {
            colors: {
                canvas: '#FFF5F8',
                surface: '#FFFFFF',
                glam: {
                    50: '#FDF2F8',
                    100: '#FCE7F3',
                    200: '#FBCFE8',
                    300: '#F9A8D4',
                    400: '#F472B6',
                    500: '#EC4899',
                    600: '#DB2777',
                    700: '#BE185D',
                    800: '#9D174D',
                    900: '#831843',
                },
                slate: {
                    850: '#151f33',
                    900: '#0F172A',
                    700: '#334155',
                    500: '#64748B',
                }
            },
            fontFamily: {
                sans: ['Plus Jakarta Sans', 'Inter', ...defaultTheme.fontFamily.sans],
                mono: ['JetBrains Mono', 'Fira Code', ...defaultTheme.fontFamily.mono],
            },
            boxShadow: {
                'glam': '0 4px 20px -2px rgba(244, 114, 182, 0.12), 0 2px 6px -1px rgba(0, 0, 0, 0.04)',
                'glam-lg': '0 10px 25px -3px rgba(244, 114, 182, 0.20), 0 4px 10px -2px rgba(0, 0, 0, 0.05)',
                'glam-glow': '0 0 20px rgba(244, 114, 182, 0.35)',
            },
            borderColor: {
                'glam-subtle': 'rgba(244, 114, 182, 0.18)',
            }
        },
    },
    plugins: [],
};
