export default {
  darkMode: "class",
  content: ["./index.html", "./src/**/*.{js,jsx}"],
  theme: {
    extend: {
      colors: {
        ink: "var(--ink)",
        invert: "var(--invert)",
        paper: "var(--paper)",
        card: "var(--card)",
        line: "var(--line)",
        pine: "var(--pine)",
        clay: "var(--clay)",
        muted: "var(--muted)",
      },
      boxShadow: {
        soft: "var(--soft)",
      },
    },
  },
  plugins: [],
};
