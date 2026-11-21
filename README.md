# Ceylon HR & Payroll

**HR and payroll for small Sri Lankan businesses, with EPF, ETF and APIT worked out automatically.**

Most employee management systems stop at "add, edit, delete". This one runs the part that takes HR teams hours every month: Sri Lankan payroll. Overtime, no-pay leave, EPF (8% + 12%), ETF (3%) and APIT income tax are calculated for every employee, payslips are produced in one click, and the statutory totals are ready to pay.

![PHP](https://img.shields.io/badge/PHP-8.1+-777BB4?logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL%20%2F%20SQLite-supported-4479A1?logo=mysql&logoColor=white)
![Bootstrap](https://img.shields.io/badge/Bootstrap-5.3-7952B3?logo=bootstrap&logoColor=white)
![Chart.js](https://img.shields.io/badge/Chart.js-4-FF6384?logo=chartdotjs&logoColor=white)

> 🔗 **Live demo:** _add your link here after deploying_
>
> | Role | Email | Password |
> |---|---|---|
> | Admin | `admin@company.test` | `admin123` |
> | Employee | `dilini@company.test` | `employee123` |
>
> The login pages also have a one-click **Use demo** button.

---

## Features

### Payroll built for Sri Lanka
- **One-click monthly payroll:** create a draft for every active employee, adjust overtime hours, no-pay days and other deductions, recalculate, then finalize.
- **EPF and ETF:** employee 8%, employer 12% and ETF 3% on EPF-liable earnings (basic + fixed allowances, minus no-pay).
- **APIT (Tax Table 01):** progressive monthly tax using the 2025/26 relief of Rs. 150,000 and 6% / 18% / 24% / 30% / 36% bands.
- **Overtime:** 1.5 × (basic ÷ 240) per hour.
- **No-pay leave:** approved no-pay leave is pulled into that month's payroll automatically.
- **Printable payslips** for every employee, which save as PDF from the browser.
- **Statutory report:** EPF/ETF contribution schedule with EPF numbers and NICs, APIT schedule, and a bank transfer list, each downloadable as CSV.

### HR
- Employees with NIC (old and new format validated), EPF number, department, designation, bank details and salary structure.
- A **live salary preview** on the employee form shows take-home pay and cost to company as you type.
- Departments with headcount and monthly salary cost.
- Leave workflow: annual (14), casual (7), sick (7) and no-pay leave, with balances, overlap checks, approve/reject and cancel.
- Employees with payroll history are marked **resigned** instead of being deleted, so past payslips stay intact.

### Dashboards
- **Admin:** payroll cost per month (stacked chart), headcount by department, pending leave with inline approve/reject, who's on leave today.
- **Employee:** last take-home pay, year-to-date net pay, EPF credited, leave balances and pay history chart.

### Security
- Passwords hashed with `password_hash()` (bcrypt), checked with `password_verify()`.
- Every query uses **PDO prepared statements**, so there's no SQL injection.
- **CSRF tokens** on every form; all output escaped with `htmlspecialchars()`.
- Session ID regenerated on login, HTTP-only cookies, login throttling after 5 failed attempts.
- Employees can only open their own finalized payslips.
- CSV exports escape cells that start with `=`, `+`, `-` or `@` to prevent formula injection.

### Also
- Dark mode, responsive layout and mobile sidebar.
- Works with **MySQL** (XAMPP / shared hosting) or **SQLite** (zero setup).

---

## Payroll rules

| Item | Rule |
|---|---|
| EPF – employee | 8% of (basic + EPF-liable allowance − no-pay) |
| EPF – employer | 12% of the same base |
| ETF – employer | 3% of the same base |
| Overtime | hours × 1.5 × basic ÷ 240 |
| No-pay | days × (basic + EPF-liable allowance) ÷ 30 |
| APIT | Monthly gross − Rs. 150,000 relief, then 6% on the next 83,333 · 18%, 24%, 30% on the next 41,667 each · 36% on the balance |

All rates live in one function, `payroll_rates()` in [`includes/payroll.php`](includes/payroll.php), so a budget change is a one-line update. The calculations have tests:

```bash
php tests/payroll_test.php
```

> ⚠️ Tax bands change with each budget. Check the latest [IRD APIT tables](https://www.ird.gov.lk) before using this for real payroll.

---

## Run it locally

### Option A: SQLite (quickest, no database server)
```bash
git clone https://github.com/SasmithaTharushan/employeeManagementSys.git
cd employeeManagementSys
DB_DRIVER=sqlite php -S localhost:8000
```
Open http://localhost:8000. The database and demo data are created on the first request.

On Windows PowerShell:
```powershell
$env:DB_DRIVER="sqlite"; php -S localhost:8000
```

### Option B: XAMPP / MySQL
1. Copy the folder into `C:\xampp\htdocs\`.
2. In phpMyAdmin, create a database called `employee_management`.
3. Open http://localhost/employeeManagementSys/install.php and click **Install**.
4. Delete `install.php` afterwards.

Database settings are read from environment variables (`DB_DRIVER`, `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASS`). For local overrides, create a `config.local.php` (it's git-ignored):
```php
<?php return ['db' => ['user' => 'root', 'pass' => 'secret']];
```

---

## Deploy

| Host | How |
|---|---|
| **Render / Railway (Docker)** | Create a web service from this repo. The included `Dockerfile` runs Apache + PHP with SQLite and demo data, so no database is needed. |
| **InfinityFree / any PHP host** | Upload the files, create a MySQL database, put its details in `config.local.php`, then open `/install.php`. |

> Vercel and GitHub Pages only serve static sites or serverless functions, so they can't run this PHP + database app.

Set `DEMO_MODE=0` to hide the demo logins on a real installation.

---

## Project structure

```
├── index.php              Landing page with a live salary calculator
├── install.php            One-time MySQL installer
├── config.php             Settings (all overridable with env vars)
├── includes/
│   ├── bootstrap.php      Loads config, session and helpers
│   ├── db.php             PDO connection + query helpers
│   ├── auth.php           Login, logout, throttling, password changes
│   ├── payroll.php        EPF / ETF / APIT engine and payroll runs
│   ├── payslip_view.php   Payslip template
│   └── layout.php         Page shell, sidebar, login page
├── admin/                 Admin console pages
├── employee/              Employee portal pages
├── database/setup.php     Schema (MySQL + SQLite) and demo data
├── assets/                CSS and JS
└── tests/payroll_test.php Calculation tests
```

## Roadmap
- [ ] Gratuity calculation for employees with 5+ years of service
- [ ] Email payslips to employees
- [ ] Public holiday calendar (Poya and mercantile holidays)
- [ ] QR code attendance
- [ ] Audit log

---

Built by [Sasmitha Tharushan](https://github.com/SasmithaTharushan).
