# 🌾 Mpulule AgroMarket

A web-based agricultural marketplace and loan management system connecting farmers and buyers in Tanzania.

**Stack:** PHP 8+ · MySQL · Bootstrap · Anypay (payments)

---

## ✨ Features

### For Users
- Full product catalog (crops, fertilizers, equipment)
- Shopping cart with AJAX updates
- Order placement & tracking
- Agricultural loan calculator & application
- In-app + email + WhatsApp notifications
- User registration, login, profile & order history

### For Admins
- Dashboard with KPIs
- Order management (approve, update, notify)
- Product & category management
- Loan application review & approval
- User management
- Contact messages inbox
- Site-wide settings

---

## 🧰 Tech Stack

| Layer | Technology |
|---|---|
| Backend | PHP 8.0+ |
| Database | MySQL 5.7+ / MariaDB 10.4+ |
| Frontend | HTML5, CSS3, Bootstrap 5, JavaScript |
| Payments | Anypay integration |
| Notifications | PHP mail() + WhatsApp deep-links |
| Server | Apache / Nginx with mod_rewrite |

---

## 🚀 Installation

### 1. Place project files
Copy the `agro_project` folder to your web server root:
- XAMPP: `C:/xampp/htdocs/agro_project/`
- WAMP: `C:/wamp64/www/agro_project/`
- Linux: `/var/www/html/agro_project/`

### 2. Create the database
phpMyAdmin → New database → name it `mpulule_agro`  
Then import `database.sql` (creates all tables + seed data).

### 3. Configure database
Edit `config.php`:

```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'mpulule_agro');