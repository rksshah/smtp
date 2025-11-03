# Contact Form with PHPMailer SMTP, Select2, SweetAlert2, and PDO

## Overview

This project is a fully functional contact form built with core PHP using PDO for secure database interactions. It features:

- Sending emails via SMTP using PHPMailer
- Multi-select dropdown enhanced with Select2.js
- User-friendly popup notifications using SweetAlert2
- Prevention of duplicate form submissions with Post/Redirect/Get (PRG) pattern

## Features

- Collects user name, email, multiple subjects, and message
- Validates inputs server-side including email format
- Saves submissions into a MySQL database
- Sends notification emails via SMTP with configurable CC recipient
- Uses Select2 for enhanced multiple selection UI
- Shows success or error messages with SweetAlert2 modals
- Prevents duplicate form submissions on page refresh by redirecting after POST

## Requirements

- PHP 7.4 or higher with PDO extension enabled
- MySQL or compatible database
- SMTP server credentials for sending emails
- PHPMailer library included locally (`PHPMailer/src/` folder)
- Internet access for loading Select2 and SweetAlert2 from CDN

## Installation

1. Clone or download this project to your web server directory.

2. Create the database table by running this SQL:

   ```sql
   CREATE TABLE contacts (
     id INT AUTO_INCREMENT PRIMARY KEY,
     name VARCHAR(100) NOT NULL,
     email VARCHAR(100) NOT NULL,
     subject VARCHAR(255) NOT NULL,
     message TEXT NOT NULL,
     created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
   );

   // Database config
$host = 'localhost';
$db   = 'your_database_name';
$user = 'your_db_user';
$pass = 'your_db_password';

// SMTP config
$smtpHost = 'smtp.example.com';
$smtpUsername = 'your_email@example.com';
$smtpPassword = 'your_email_password';
$smtpPort = 587; // or 465
$smtpSecure = 'tls'; // or 'ssl'
$adminEmail = 'admin@example.com'; // recipient email
