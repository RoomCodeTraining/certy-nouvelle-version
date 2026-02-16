/** @type {import('tailwindcss').Config} */
export default {
    content: [
        "./resources/**/*.blade.php",
        "./resources/**/*.js",
        "./resources/**/*.vue",
    ],
    theme: {
        extend: {
            fontFamily: {
                sans: [
                    "Outfit",
                    "ui-sans-serif",
                    "system-ui",
                    "sans-serif",
                ],
            },
            colors: {
                brand: {
                    primary: "rgb(var(--color-brand-primary) / <alpha-value>)",
                    secondary: "rgb(var(--color-brand-secondary) / <alpha-value>)",
                    accent: "rgb(var(--color-brand-accent) / <alpha-value>)",
                },
            },
        },
    },
    plugins: [],
};
