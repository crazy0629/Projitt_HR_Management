---

# 👥 Projitt – Platform Overview

## Welcome to the Projitt Engineering & Design Workspace

This repository serves as the **central documentation hub** for all internal teams working on **Projitt**, our AI-powered talent acquisition and human resources platform. Here you’ll find everything from user flows and interface descriptions to automation logic and development checklists — designed to keep recruiters and applicants connected through intuitive, intelligent tools. 

Our fully integrated enterprise platform supporting the following functional modules —

• Human Resources Management 

• Contract & Vendor Management 

• Asset & Facility Management 

• Finance & Operations 

• Analytics & Reporting

---

## 🧭 HRM Platform Overview

**Empower** people operations through automation, clarity, and control.
Projitt's HR module gives teams a central, AI-enhanced platform to manage every aspect of the employee lifecycle — from hiring and onboarding to performance tracking and offboarding.

Streamlined onboarding workflows with document automation

Central employee directory with org chart and profile cards

Time-off management with request/approval flows

Performance review templates and tracking

Policy acknowledgment and compliance tracking

Role-based access to HR records

AI-powered resume parsing and smart role matching

Exit interviews, offboarding tasks, and knowledge handover

**HR Suite**

*Recruitment*
- Job posting interface (AI job description assist)

- Application intake and ATS (Applicant Tracking System)

- Interview scheduling

- Basic 2-way messaging

-Trigger-based acceptance/rejection emails

*Onboarding*
- Offer letter e-sign

- Document collection & checklist

- Form auto-fill for bank/personal/benefits

- Orientation/training scheduler

- Progress tracker

- Data sync to performance & payroll modules

*Performance & Talent*
- Simple 360° review setup (Upward/Downward/Peer)

- Customizable review parameters

- Learning portal access (manual entry)

- Promotion/succession planning tracker

*Leave & Attendance*
- Time-off request and approval flow

- PTO accrual logic

- Web-based clock-in/out

- Timesheet for salaried staff

- Basic geofencing for remote tracking

*Payroll*
- Multi-currency support

- Direct deposit & paycard options

- Instant preview & batch processing

- Tax form generator (W-2/W-4 preview)

- Quarterly tax summary view

---

## 🔐 User Portals

### 👨‍💼 For Recruiters

| Feature                  | Summary                                    |
| ------------------------ | ------------------------------------------ |
| **Post Job**             | Manually or with AI assistance             |
| **Review Applicants**    | Ranked using AI score + keyword matching   |
| **Schedule Interviews**  | Send invites with calendar integration     |
| **Chat with Applicants** | Secure, real-time messaging                |
| **Auto Decisions**       | Offers/rejections triggered post-interview |
| **Close Job**            | Archive and notify applicants              |

### 👩‍💻 For Applicants

| Feature                   | Summary                                     |
| ------------------------- | ------------------------------------------- |
| **Apply for Job**         | Submit via form, resume, or LinkedIn        |
| **Respond to Recruiters** | Dashboard messaging + email sync            |
| **Attend Interview**      | Confirm via calendar; get reminders         |
| **Get Notified**          | Offer or rejection sent via dashboard/email |

---

## 🧩 Feature Flow Architecture

```
Recruiter Portal
├── Dashboard
├── Post Job
│   └── AI Assist (Job Description)
├── Applicant List
│   └── AI Scoring + Filters
├── Candidate Profile
├── Schedule Interview
├── Messaging Panel
├── Automation Settings
└── Close Job Workflow

Applicant Portal
├── Job Listings
├── Application Tracker
├── Messages
├── Interview Calendar
├── Offer / Rejection Notice
└── Profile + Resume Settings
```

---

## 🛠 Design + Development Tasks

| Task                   | Status  | Description                   |
| ---------------------- | ------- | ----------------------------- |
| Define IA              | ☐ To Do  | Recruiter vs. Applicant flows |
| Wireframe Dashboards   | ☐ To Do | Both user types               |
| Job Post UI            | ☐ To Do | Manual + AI                   |
| Applicant Scoring UI   | ☐ To Do | Tags, filters, scores         |
| Interview Scheduler    | ☐ To Do | Calendar interface            |
| Chat UI                | ☐ To Do | Sync with email               |
| Automation Rules Panel | ☐ To Do | Template + logic editor       |

---

## 📂 Suggested Repository Structure

```
projitt-internal-hub/
├── README.md
├── docs/
│   ├── user-flows.md
│   ├── automation-logic.md
│   └── ai-scoring.md
├── design-docs/
│   ├── recruiter-wireframes.png
│   ├── applicant-ui-mockup.png
│   └── figma-links.txt
├── setup/
│   ├── onboarding-checklist.md
│   └── dev-env-setup.md
└── .github/
    └── workflows/
        └── preview-deploy.yml
```

---

## ✅ Developer Onboarding Checklist

* [ ] Clone this repo
* [ ] Review `user-flows.md` and `ai-scoring.md`
* [ ] Sync with design team (check `/design-docs`)
* [ ] Set up dev environment (`setup/`)
* [ ] Join Slack #projitt-platform
* [ ] Use `feat/`, `fix/`, `docs/` prefixes in commits

---

## 📊 Tools & Tech Stack

* **Frontend**: React + TailwindCSS
* **Backend**: Node.js + Express
* **Database**: PostgreSQL
* **AI Services**: OpenAI, LangChain
* **Messaging**: WebSockets + Email Sync
* **Deployment**: Vercel + GitHub Actions
* **Design**: Figma

---
