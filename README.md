# PHP Exam Project - Exam Machine

## Structure
- `services/`: Contains independent services (e.g., PHPMailer, mPDF).
- `vendor/`: Composer dependencies.
- `.env`: Sensitive environment variables.
- `index.php`: Front-end form.
- `send.php`: Backend logic for sending mail.
- `test_pdf.php`: Test script for PDF generation.

## Dependencies
- `phpmailer/phpmailer`: For sending emails via SMTP.
- `vlucas/phpdotenv`: For loading environment variables from `.env`.
- `mpdf/mpdf`: For generating PDF documents from HTML.

## Setup
1. Run `composer install` (if moving to a new environment).
2. Configure `.env` with your SMTP credentials.
