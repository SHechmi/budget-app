# Budget App

Budget App is a PHP/MySQL web application for tracking personal and shared finances. It lets users manage budgets, transactions, categories, alerts, and collaborative budgets from a simple dashboard.

## Features

- User registration and login
- Admin validation and account management
- Personal and shared budgets
- Budget categories with spending limits
- Income and expense tracking
- Alerts when a budget reaches or exceeds its threshold
- Profile management and password changes
- Dashboard statistics and charts
- Commenting on transactions in shared budgets

## Tech Stack

- PHP
- MySQL / MariaDB
- PDO for database access
- HTML, CSS, and Bootstrap-based layout

## Project Structure

- `index.php`: public homepage and landing page
- `config/`: application and database configuration
- `controllers/`: request handling and business actions
- `models/`: data models
- `views/`: application pages
- `includes/`: shared layout and helper includes
- `utils/`: authentication, validation, and helper functions
- `assets/`: CSS and static assets
- `database/db.sql`: database schema

## Requirements

- PHP 8.x or compatible version
- MySQL or MariaDB
- Apache/Nginx local server such as XAMPP

## Installation

1. Copy the project into your web server directory, for example `c:\xampp\htdocs\budget_app`.
2. Start Apache and MySQL in XAMPP.
3. Create the database and import the schema from `database/db.sql`.
4. Update `config/database.php` if your local database credentials are different.
5. Open the application in your browser:

```text
http://localhost/budget_app/
```

## Default Database Configuration

The current local configuration uses:

- Host: `localhost`
- Database: `budget_app`
- Username: `root`
- Password: empty

Change these values in `config/database.php` before deploying outside a local development environment.

## Main Pages

- Home: `index.php`
- Login: `views/login.php`
- Register: `views/register.php`
- Dashboard: `views/dashboard.php`
- Budgets: `views/budgets.php`
- Transactions: `views/transactions.php`
- Categories: `views/categories.php`
- Alerts: `views/alertes.php`
- Profile: `views/profile.php`
- Admin area: `views/admin.php`

## Database Overview

The schema includes tables for:

- `utilisateurs`
- `categories`
- `budgets`
- `budget_categories`
- `budgets_membres`
- `transactions`
- `commentaires`
- `alertes`

## Notes

- The public homepage displays live statistics from the database.
- Shared budgets support members and comments.
- Alerts are generated from budget thresholds and spending activity.

## License

No license file is currently included.
