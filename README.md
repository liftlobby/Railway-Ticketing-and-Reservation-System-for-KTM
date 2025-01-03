# KTM Railway System

A comprehensive railway ticket booking and management system for KTM (Keretapi Tanah Melayu).

## Features

### For Passengers
- **Account Management**
  - User registration and login
  - Profile management
  - View booking history

- **Ticket Booking**
  - Search available trains
  - Select seats
  - Make payments
  - View booking details
  - Download/Print tickets with QR codes

### For Staff
- **Ticket Management**
  - Scan QR codes for ticket verification
  - Manual ticket ID entry
  - Real-time ticket status verification
  - View detailed ticket information

- **Train Management**
  - View train schedules
  - Monitor train status
  - Update train information

## Technical Requirements

### Server Requirements
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Apache Web Server
- XAMPP (recommended for local development)

### Browser Requirements
- Modern web browser with JavaScript enabled
- Camera access for QR code scanning (staff portal)
- Support for HTML5 and CSS3

### Dependencies
- Bootstrap 5.3.0
- Boxicons 2.0.7
- HTML5-QRCode 2.3.8
- jQuery (latest version)

## Installation

1. **Set up XAMPP**
   - Install XAMPP
   - Start Apache and MySQL services

2. **Database Setup**
   - Import the database schema from `database/railway_system.sql`
   - Configure database connection in `config/database.php`

3. **Project Setup**
   - Clone/copy the project to `htdocs` directory
   - Ensure proper file permissions
   - Configure base URL in configuration files

## Usage

### Passenger Portal
1. Access the system through `http://localhost/KTM/`
2. Register a new account or login
3. Search for available trains
4. Select seats and make booking
5. Complete payment
6. Download/print ticket with QR code

### Staff Portal
1. Access the staff portal through `http://localhost/KTM/staff/`
2. Login with staff credentials
3. Use QR scanner or manual entry to verify tickets
4. View and manage train schedules

## Security Features
- Session management
- Password hashing
- Input validation
- SQL injection prevention
- XSS protection
- CSRF protection

## Error Handling
- Comprehensive error logging
- User-friendly error messages
- Transaction rollback for failed operations
- Proper exception handling

## File Structure
```
KTM/
├── config/
│   └── database.php
├── includes/
│   └── MessageUtility.php
├── staff/
│   ├── scan_qr.php
│   ├── verify_ticket.php
│   └── ...
├── assets/
│   ├── css/
│   ├── js/
│   └── images/
├── process_booking.php
├── generate_qr.php
└── README.md
```

## Troubleshooting

### QR Scanner Issues
1. Ensure camera permissions are granted
2. Try switching cameras if available
3. Check browser console for error messages
4. Ensure proper lighting for QR code scanning
5. Use manual ticket ID entry as backup

### Common Issues
- Database connection errors: Check database configuration
- Session issues: Clear browser cache and cookies
- Payment processing: Verify payment gateway settings
- QR generation: Ensure proper permissions for temp directories

## Updates and Maintenance
- Regular security updates
- Database optimization
- Performance monitoring
- Bug fixes and feature enhancements

## Contributing
1. Fork the repository
2. Create a feature branch
3. Commit your changes
4. Push to the branch
5. Create a pull request

## License
This project is proprietary software. All rights reserved.

## Support
For technical support or queries:
- Email: support@ktm.com.my
- Phone: +60 XXXXXXXXX
- Hours: 9 AM - 6 PM (MYT)
