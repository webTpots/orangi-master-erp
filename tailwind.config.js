import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],
    theme: {
        extend: {
            fontFamily: {
                sans: ['Inter', ...defaultTheme.fontFamily.sans],
                display: ['Poppins', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                brand: { 50:'#FBF3EA', 100:'#F6E4D3', 200:'#EBC4A6', 300:'#DFA07A', 400:'#D17C4E', 500:'#C1502E', 600:'#9C3F24', 700:'#7A2D18', 800:'#5C1F10', 900:'#3D1309' },
                surface: { DEFAULT:'#FFFFFF', secondary:'#FBF3EA', tertiary:'#F1E4D3', border:'#E4D5C3', 'border-light':'#EFE3D3' },
                content: { DEFAULT:'#2B211C', secondary:'#8A7A6D', tertiary:'#5C4F45', muted:'#A89584' },
                success: { 50:'#E6F4EA', 500:'#1E8E3E', 600:'#167B34' },
                danger: { 50:'#FDEAE8', 500:'#D93025', 600:'#B5281E', border:'#F6C9C4' },
                warning: { 50:'#FFF3D6', 500:'#B8790A', 600:'#9A6508', border:'#F3DFA1' },
                neutral: { 50:'#F1F1F1', 500:'#6B7280', border:'#DEDEDE' },
                sidebar: { bg:'#1A1410', text:'#C7B8A6', active:'#C1502E' },
            },
            boxShadow: {
                card: '0 1px 3px rgba(0,0,0,0.06), 0 1px 2px rgba(0,0,0,0.04)',
                'card-hover': '0 4px 12px rgba(0,0,0,0.08)',
                dropdown: '0 10px 30px rgba(0,0,0,0.12)',
                modal: '0 25px 50px rgba(0,0,0,0.2)',
                brand: '0 4px 14px rgba(193,80,46,0.25)',
            },
        },
    },
    plugins: [forms],
};
