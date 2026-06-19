/** @type {import('tailwindcss').Config} */
module.exports = {
  darkMode: 'class', // <--- ESTA LINHA É OBRIGATÓRIA
  content: [
    "./*.php",
    "./**/*.php",
    "./PAP/**/*.php",
    "./PAP/PAP-AplicacaoWeb-PequenosPassos/**/*.php"
  ],
  theme: { extend: {} },
  plugins: [],
}
