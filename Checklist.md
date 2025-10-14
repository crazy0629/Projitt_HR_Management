# ✅ Projitt Development & Collaboration Checklist

This checklist outlines our team’s shared expectations for consistent, high-quality development. It includes naming conventions, testing standards, code review procedures, and best practices to ensure a smooth, scalable workflow.

---

## 1. 🔠 Naming Conventions

### \[ ] Branch Naming

* All branches must start with `hrmprojitt_`
* Use the following prefixes for clarity:

  * `hrmprojitt_feature/` → new features
  * `hrmprojitt_bugfix/` → bug fixes
  * `hrmprojitt_hotfix/` → urgent production patches
  * `hrmprojitt_docs/` → documentation-only changes

✅ Examples:
`hrmprojitt_feature/interview-scheduler`
`hrmprojitt_bugfix/chat-ui-freeze`

---

### \[ ] Commit Messages

* Follow [Conventional Commits](https://www.conventionalcommits.org/)
  Examples:

  * `feat: add AI scoring indicators to applicant list`
  * `fix: resolve calendar sync issue in scheduler`
* Keep messages concise and informative
* Reference issues or PRs where applicable: `feat: add onboarding module (#21)`

---

### \[ ] File and Directory Naming

* Use lowercase with **hyphens** or **underscores**:
  `job-post-form/`, `utils_helpers/`
* No spaces, no capital letters
* Maintain consistency across modules

---

### \[ ] Variable & Function Naming

* Use **camelCase** for JavaScript (e.g., `getInterviewSlot`)
* Use **snake\_case** for Python (e.g., `fetch_user_data`)
* Always use descriptive names (no `tmp`, `x`, or `stuff`)
* Avoid abbreviations unless standard (e.g., `id`, `api`)

---

## 2. 🧪 Testing Standards

### \[ ] Test Coverage

* Write unit tests for all new features
* Focus on critical business logic and user flows
* Use stubs/mocks for external systems

### \[ ] Test Automation

* Integrate tests into GitHub Actions workflows
* All tests must pass before PR merge

### \[ ] Test Naming & Structure

* JS/TS: `Component.test.js`
* Python: `test_module.py`
* Group related tests by feature or module

### \[ ] Code Quality Checks

* Run linters (e.g., ESLint, Black) on save or commit
* Use static analysis tools in CI/CD
* Never ignore errors or warnings without good reason

---

## 3. 🛠 Code Review & Refinement

### \[ ] Pull Requests

* PRs are required for all changes
* Use clear, informative titles and descriptions
* Include screenshots or screen recordings for UI changes
* Link relevant issues: `Closes #33`

### \[ ] Code Review

* Review others’ PRs constructively and promptly
* Focus on:

  * Readability
  * Modularity
  * Performance
  * Test completeness
* Confirm CI passes before approval

### \[ ] Refinement

* Apply requested feedback before merging
* Refactor messy or redundant code
* Rebase or merge latest `main` before finalizing PR

---

## 4. 📚 Documentation

### \[ ] Update Documentation

* Keep all relevant `.md` files up to date
* Document new features, setup steps, or APIs
* Use `/docs/` folder to organize guides and specifications

---

## 5. 🧩 General Best Practices

### \[ ] Communication

* Use clear commit messages and PR titles
* Propose large changes via Slack before implementation

### \[ ] Issue Tracking

* Use GitHub Issues or Projects to track:

  * Features
  * Bugs
  * Enhancements
* Link commits and PRs to issues for transparency

### \[ ] Security & Privacy

* **NEVER** commit API keys, passwords, or personal data
* Use `.env` and `.gitignore` properly
* Review open-source dependencies regularly

---

## 💡 Final Reminder

Consistency = Collaboration.
Clean branches. Clear commits. Constructive reviews.
Let’s build Projitt right — together.

---

