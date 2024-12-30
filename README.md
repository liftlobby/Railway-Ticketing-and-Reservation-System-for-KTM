# KTM Railway System

A comprehensive railway ticketing system with QR code-based ticket verification.

## Table of Contents
- [Features](#features)
- [System Requirements](#system-requirements)
- [Installation](#installation)
- [System Architecture](#system-architecture)
- [User Guide](#user-guide)
- [Staff Guide](#staff-guide)
- [Security Features](#security-features)
- [Database Structure](#database-structure)

## Features

### User Features
- User registration and authentication
- Train schedule browsing
- Online ticket booking
- Secure payment processing
- QR code-based tickets
- Booking history
- Ticket cancellation

### Staff Features
- Secure staff login
- QR code ticket scanning
- Real-time ticket verification
- Verification history
- Admin dashboard

### Security Features
- Argon2id password hashing
- Token-based authentication
- QR code encryption
- Account lockout protection
- Activity logging
- Secure session management

## System Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher
- XAMPP (or similar web server)
- Modern web browser with camera access
- Internet connection for payment processing

## Installation

1. Clone the repository to your XAMPP htdocs folder:
```bash
git clone [repository-url] /path/to/xampp/htdocs/KTM
```

2. Import the database:
```bash
mysql -u root -p < railway_system.sql
```

3. Configure database connection in `config/database.php`:
```php
$host = "localhost";
$username = "your_username";
$password = "your_password";
$database = "railway_system";
```

4. Default admin credentials:
```
Username: admin
Email: admin@railway.com
Password: admin123
```

## System Architecture

### Core Components

1. **User Management**
   - `register.php`: New user registration
   - `login.php`: User authentication
   - `profile.php`: User profile management

2. **Ticket Booking Flow**
   ```
   ticketing.php → process_booking.php → payment.php → payment_success.php → generate_qr.php
   ```

3. **QR Code System**
   ```
   generate_qr.php (creation) → staff/scan_qr.php (scanning) → staff/verify_ticket.php (verification)
   ```

### Directory Structure

```
KTM/
├── config/
│   └── database.php
├── includes/
│   ├── TokenManager.php
│   ├── PasswordUtility.php
│   └── MessageUtility.php
├── staff/
│   ├── dashboard.php
│   ├── scan_qr.php
│   └── verify_ticket.php
├── Head_and_Foot/
│   ├── header.php
│   └── footer.php
└── assets/
    ├── css/
    ├── js/
    └── images/
```

## User Guide

### Booking a Ticket

1. Register/Login to your account
2. Browse available trains on the ticketing page
3. Select your preferred schedule
4. Choose number of seats (max 4)
5. Complete payment
6. Download or save your QR code ticket

### Managing Bookings

1. View booking history in your profile
2. Download QR codes for active tickets
3. Cancel tickets if needed (subject to policy)
4. View payment history

## Staff Guide

### Ticket Verification

1. Login to staff portal
2. Access QR scanner page
3. Allow camera access when prompted
4. Scan passenger's QR code
5. Verify ticket details on screen

### Admin Functions

1. Manage train schedules
2. View verification logs
3. Handle refund requests
4. Generate reports

## Security Features

### Password Security
- Argon2id hashing algorithm
- Pepper addition for extra security
- Account lockout after failed attempts

### QR Code Security
- Encrypted token generation
- One-time use verification
- 24-hour token validity
- Real-time validation

### System Security
- Prepared SQL statements
- CSRF protection
- XSS prevention
- Session security
- Activity logging

## Database Structure

### Main Tables
- `users`: User accounts
- `staffs`: Staff accounts
- `schedules`: Train schedules
- `tickets`: Booking information
- `payments`: Payment records
- `auth_tokens`: QR code tokens
- `ticket_verifications`: Scan logs
- `activity_logs`: System audit trail

### Key Relationships
```
users → tickets → auth_tokens
tickets → payments
tickets → ticket_verifications
schedules → tickets
```

## Support

For technical support or queries:
- Email: support@ktm.com
- Phone: +60 XXXXXXXXX

## License

This project is proprietary and confidential. Unauthorized copying or distribution is prohibited.
