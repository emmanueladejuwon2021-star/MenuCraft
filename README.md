# MenuCraft (PHP Edition)

A digital restaurant live menu, ordering system, and kitchen management application built in PHP and SQLite.

Kitchen staff can mark dishes sold out, managers can change prices in bulk, and guests can open the live menu from a table QR code.

---

## 🍽 Features

1. **Today's Board & Live Menu (`index.php` / `menu.php`)**
   - Guest view with instant search, category filtering, diet tags (Gluten-Free, Vegetarian, Spicy), and stock badges.
   - Add dishes directly to plate (cart).

2. **Guest Plate & Checkout (`plate.php` / `pay.php`)**
   - Increment or decrement portion quantities (up to 20).
   - Checkout with table/pickup note — no credit card needed (counter payment).
   - Authoritative pricing calculated on the server to prevent tamper.

3. **My Orders (`my-orders.php`)**
   - Real-time order tracking: *Sent to the kitchen* (New) → *On the fire* (Cooking) → *Ready to pick up* (Ready) → *Served*.
   - Ticket reference search.

4. **Dish Manager (`dishes.php`)**
   - Add, edit, and remove dishes with photo links, prep time, price, tags, and descriptions.
   - Create and delete categories.

5. **Stock Toggle (`stock.php`)**
   - Big, tactile on/off switches designed for kitchen tablets to quickly mark dishes sold out or in stock.

6. **Quick Pricing (`pricing.php`)**
   - Bulk adjust dish prices for an entire category by percentage (+10%, -5%) or fixed amount (+₦500, -₦200).
   - Live preview before applying with automatic zero-floor protection.

7. **QR Code & Share (`share.php`)**
   - Generates scannable QR code linking to the live menu.
   - Download QR code image as PNG.
   - Copy guest menu link.
   - Copy hidden kitchen door link.
   - Manage restaurant name and currency symbol (₦, $, €, £, etc.).

---

## 🔐 Guest Site and Kitchen Door

- **Guests** only see Home, Menu, Plate, Orders, and Account.
- **Kitchen staff** access the secret kitchen entrance:

```
http://localhost:8000/iyabisi/kitchenlock/
```

- **Bookmark that link** on the kitchen tablet. Do not print it on guest QR codes.
- The first account registered through the kitchen door automatically claims the **staff** role.
- Later staff accounts must be created by someone already signed in as staff.

---

## 🚀 Getting Started

### Prerequisites

- PHP 8.1 or higher with PDO SQLite extension enabled (`pdo_sqlite`).

### Running Locally

Clone the repository and start PHP's built-in development server from the project root:

```bash
php -S localhost:8000
```

Open your browser and visit:
- **Guest Menu:** [http://localhost:8000](http://localhost:8000)
- **Kitchen Entrance:** [http://localhost:8000/iyabisi/kitchenlock/](http://localhost:8000/iyabisi/kitchenlock/)

The database (`menu.db`) will be automatically created and seeded with sample dishes (Beef Suya, Puff Puff, Asun, Egusi, Jollof Rice, Chapman, etc.) on first launch.

---

## 🧪 Running Tests

MenuCraft includes a pure PHP test suite covering price calculation rules, plate validation, and order visibility security:

```bash
php tests/run.php
```

All tests run without requiring any third-party test dependencies.

---

## 📁 Project Structure

```
├── assets/
│   ├── css/
│   │   └── app.css             # Theme variables & custom UI styles
│   └── js/
│       ├── app.js              # Theme switcher, toasts, plate & stock AJAX
│       └── qrcode.min.js       # Standalone client QR generator
├── includes/
│   ├── auth.php                # Authentication & staff claim logic
│   ├── db.php                  # PDO SQLite connection & seeder
│   ├── footer.php              # Shared HTML footer
│   ├── header.php              # Shared header, navbar & mobile bottom bar
│   ├── helpers.php             # Cart, ticket session, currency formatters
│   ├── kitchen_nav.php         # Staff navigation subheader
│   ├── order_build.php         # Server-side order verification & visibility
│   └── pricing.php             # Bulk pricing calculation engine
├── iyabisi/
│   └── kitchenlock/
│       └── index.php           # Hidden kitchen door entrance
├── tests/
│   ├── OrderBuildTest.php      # Order validation unit tests
│   ├── PricingTest.php         # Price calculation unit tests
│   └── run.php                 # PHP test runner
├── index.php                   # Guest Landing Page
├── menu.php                    # Live Menu & Filters
├── plate.php                   # Guest Cart / Plate
├── pay.php                     # Table Note & Checkout
├── my-orders.php               # Guest Order Tracking
├── guest-account.php           # Guest Sign-In / Sign-Up
├── dishes.php                  # Staff Dish Manager
├── stock.php                   # Staff Stock Switches
├── pricing.php                 # Staff Quick Pricing
├── orders.php                  # Staff Kitchen Orders Board
├── share.php                   # Staff QR Code & Share
├── account.php                 # Staff Account Settings
├── logout.php                  # Session logout
├── .htaccess                   # Apache URL rewrite configuration
└── README.md
```

---

## 🌐 Deployment

### Apache / XAMPP / WAMP / cPanel
- Copy the project into your web root (e.g., `htdocs/MenuCraft`).
- The provided `.htaccess` file handles URL routing and blocks direct HTTP access to `menu.db`.

### Nginx
Configure fastcgi to point to `index.php` and allow direct script execution.

### Docker / Cloud Hosting
Run any standard PHP 8+ container pointing to the project directory.
