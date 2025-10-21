# Install

A modern Next.js front-end project with enforced code style, linting, and a shared development environment for maximum consistency and productivity.

---

## 🚀 Quick Start

1. **Clone the repository:**

   ```bash
   git clone <your-repo-url>
   cd frontend
   ```

2. **Install dependencies:**

   ```bash
   npm install
   ```

3. **Start the development server:**

   ```bash
   npm run dev
   ```

   Open [http://localhost:3000](http://localhost:3000) in your browser.

4. **Set up environment variables:**
   Copy the example environment file and update it as needed:
   ```bash
   cp .env.example .env
   ```
   The `.env` file contains environment-specific variables (such as API keys or secrets) required for the app to run. Edit this file to match your local setup if necessary.

---

## 🛠️ Shared Development Environment

This project is pre-configured for a consistent developer experience. **No manual setup is required after cloning!**

### 1. Recommended VS Code Extensions

When you open the project in VS Code, you’ll be prompted to install these extensions (see `.vscode/extensions.json`):

- Prettier - Code formatter (`esbenp.prettier-vscode`)
- ESLint (`dbaeumer.vscode-eslint`)
- Auto Import (`formulahendry.auto-import`)
- IntelliCode (`VisualStudioExptTeam.vscodeintellicode`)
- GitLens (`eamodio.gitlens`)
- Stylelint (`stylelint.vscode-stylelint`)
- Tailwind CSS IntelliSense (`bradlc.vscode-tailwindcss`)
- ES7+ React/Redux/React-Native snippets (`dsznajder.es7-react-js-snippets`)
- Reactjs code snippets (`xabikos.ReactSnippets`)

### 2. Workspace Settings

The following settings are committed in `.vscode/settings.json`:

```json
{
  "editor.formatOnSave": true,
  "editor.defaultFormatter": "esbenp.prettier-vscode",
  "editor.codeActionsOnSave": {
    "source.fixAll.eslint": "explicit"
  }
}
```

- **Format on save** is enabled.
- **Prettier** is the default formatter.
- **ESLint** auto-fix is enabled on save.

### 3. Code Style & Linting

- **Prettier** is configured in `.prettierrc`:
  ```json
  {
    "semi": true,
    "singleQuote": true,
    "printWidth": 100,
    "tabWidth": 2,
    "trailingComma": "es5"
  }
  ```
- **ESLint** is configured in `.eslintrc.json`:
  ```json
  {
    "extends": ["next/core-web-vitals", "plugin:prettier/recommended"],
    "rules": {
      "prettier/prettier": ["error"]
    }
  }
  ```
- Both tools are included in `devDependencies` and ready to use.

---

## 📦 Scripts

- **Start development server:**  
  `npm run dev`
- **Build for production:**  
  `npm run build`
- **Start production server:**  
  `npm run start`
- **Lint code:**  
  `npm run lint`
- **Format code:**  
  `npm run format`

---

## 📝 How to Contribute

1. **Pull the latest changes.**
2. **Install dependencies** if you haven’t already:  
   `npm install`
3. **Use the recommended VS Code extensions** for best results.
4. **Write code!**
   - All code will be auto-formatted and linted on save.
   - Run `npm run lint` and `npm run format` before committing.
5. **Open a pull request** with your changes.

---

## ✅ Acceptance Criteria Coverage

- **Recommended plugins list is documented** (see above and `.vscode/extensions.json`)
- **VS Code workspace settings** are committed (`.vscode/settings.json`)
- **Prettier and ESLint config files** are included and working
- **Developers can pull the project and begin coding without manual config**

---

## 📚 Learn More

- [Next.js Documentation](https://nextjs.org/docs)
- [Prettier Documentation](https://prettier.io/docs/en/index.html)
- [ESLint Documentation](https://eslint.org/docs/latest/)

---
