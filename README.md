# EventHub Pro — Smart Event Management Platform

EventHub Pro is a high-performance PHP platform designed for professional event organization (conferences, hackathons, and meetups). Built with a custom **MVC Architecture**, it provides a seamless experience for both organizers and attendees.

## 🚀 Key Features

-   **X-Inspired UI/UX**: A modern, sleek dark-themed interface optimized for performance and usability.
-   **Dynamic Event Discovery**: Real-time event filtering and searching using AJAX/Fetch API with debounced inputs.
-   **Smart Registration**: Secure attendee registration system with automatic capacity management and duplicate prevention.
-   **Automated Ticketing**: Dynamic PDF ticket generation (via mPDF) featuring unique QR codes for entry verification.
-   **Organizer Intelligence**: Real-time dashboard with 30-second auto-refresh and automated email alerts when events reach 80% capacity.
-   **Secure PDO Layer**: Robust database interactions protected against SQL injection using prepared statements and a Singleton pattern.

## 🛠️ Technical Stack

-   **Backend**: PHP (Custom MVC Architecture)
-   **Database**: MySQL (PDO)
-   **Frontend**: Tailwind CSS (Custom X Platform Theme), Vanilla JavaScript (ES6+)
-   **Mailing**: PHPMailer
-   **PDF Generation**: mPDF
-   **Environment**: Dotenv for secure configuration

## 📦 Installation & Setup

### 1. Requirements
-   PHP 8.0+
-   Composer
-   MySQL

### 2. Configuration
1.  Clone the repository.
2.  Install dependencies:
    ```bash
    composer install
    ```
3.  Copy `.env.example` to `.env` and configure your database and SMTP credentials:
    ```env
    DB_NAME=eventhub_pro
    DB_USER=root
    DB_PASS=
    # SMTP details...
    ```

### 3. Database Initialization
Import the schema located at `database/schema.sql` into your MySQL instance:
```sql
# via command line or phpMyAdmin
SOURCE database/schema.sql;
```

### 4. Server Setup
Point your web server (Apache/Nginx) document root to the `public/` directory.

## 📂 Project Structure

-   `app/`: Application logic (Models, Views, Controllers).
-   `core/`: Custom MVC framework core (Router, Database, Base Controller).
-   `public/`: Front controller and public assets (JS, CSS).
-   `database/`: SQL schema and migration scripts.
-   `services/`: Core helper services for Mail and PDF generation.

## 📝 License
This project was developed as part of the Advanced PHP Module at **ENSA Marrakech**.
