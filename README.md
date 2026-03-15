# FinPulse - Personal Finance Tracker System

## 1. Introduction
Managing personal finances is essential for students and working individuals. However, many people fail to properly track their income and expenses, leading to poor budgeting and financial instability.

FinPulse (formerly Personal Finance Tracker System) is a full-stack web application designed to provide a modern, responsive, and secure platform for users to manage their financial transactions effectively. Currently, the **Front-End (Phase 02)** of the system is fully designed and operational with dynamic UI elements.

## 2. Features
### UI/UX
- **Premium Landing Page:** A beautiful, conversion-focused home page that introduces the system features.
- **Modern Dashboard:** View a snapshot of your finances, including Total Balance, Income, Expenses, and custom goals.
- **Dark/Light Mode:** Seamless toggling between light and dark themes with persistent user preferences.
- **Responsive Layout:** Built with Bootstrap 5 to ensure perfect rendering across desktop, tablet, and mobile devices.

### Functionality (Client-Side)
- **Interactive Charts:** Utilizes Chart.js to visualize Income vs. Expense trends and Expense Distributions by category.
- **Transactions Management:** A dedicated view for searching, filtering, and managing individual transactions.
- **Authentication Pages:** Sleek Login and Registration forms equipped with real-time Bootstrap form validation.

## 3. Technology Stack (Phase 02)
- **Structure:** HTML5
- **Styling:** CSS3, Bootstrap 5 (Customized)
- **Interactivity:** JavaScript (ES6)
- **Data Visualization:** Chart.js
- **Icons:** Bootstrap Icons

## 4. System Architecture
The application currently models the client-side of the architecture:
```text
User  →  Web Browser  →  HTML + Bootstrap Interface  →  JavaScript (Client-Side Logic & Charts)
```
*(Note: PHP and MySQL will be integrated during Phase 03 Back-End Development).*

## 5. File Structure
```text
personal-finance-tracker/
│
├── index.html           # Landing Page / Home Page
├── dashboard.html       # Main application dashboard (Charts, Metrics)
├── transactions.html    # Detailed transactions table with filters
├── login.html           # User login page
├── register.html        # User registration page
└── assets/
    ├── css/
    │   └── style.css    # Custom global styles and custom theme variables
    └── js/
        └── app.js       # Client-side logic for charts, theme toggling, and validation
```

## 6. How to Run
Since this is a front-end interface, no local server (like XAMPP or Node.js) is required to view the pages.
1. Download or clone this repository to your local machine.
2. Navigate to the project folder.
3. Double-click on `index.html` to open the landing page in your default web browser.
4. From the landing page, you can navigate seamlessly to the `register.html`, `login.html`, and `dashboard.html` pages via the provided buttons.

## 7. Future Enhancements (Phase 03 integration)
- Implement Back-End logic with PHP and link to MySQL Database.
- Secure user authentication with session management.
- Real CRUD (Create, Read, Update, Delete) operations for the transactions table.
- Admin dashboard, data export functionalities (PDF/Excel), and email notifications.

---
**Developed as part of the Personal Finance Tracker System Project Proposal.**
