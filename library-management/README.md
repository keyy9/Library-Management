# Library Management System - XAMPP Setup Instructions

## Prerequisites
- XAMPP installed on your computer
- PHP 7.4 or higher
- MySQL 5.7 or higher

## Installation Steps

1. **Copy Project Files**
   - Copy the entire `library-management` folder to your XAMPP's `htdocs` directory
   - The path should be: `C:\xampp\htdocs\library-management` (Windows) or `/Applications/XAMPP/htdocs/library-management` (Mac)

2. **Create Database**
   - Open XAMPP Control Panel and start Apache and MySQL services
   - Open your web browser and go to `http://localhost/phpmyadmin`
   - Click on "Import" in the top menu
   - Click "Choose File" and select the file: `sql/library.mysql.sql` from the project directory
   - Click "Go" to import the database structure and sample data

3. **Configure the Application**
   The database configuration is already set up in `config/config.php` with these default settings:
   - Database Name: library_management
   - Username: root
   - Password: (empty)
   - Host: localhost

   If your XAMPP MySQL settings are different, modify these values in `config/config.php`

4. **Access the Application**
   - Open your web browser
   - Go to: `http://localhost/library-management/public`
   - Login with default admin credentials:
     - Username: admin
     - Password: admin123

## Directory Structure
```
library-management/
├── assets/           # Global assets (CSS, JS)
├── config/          # Configuration files
├── includes/        # Common PHP includes
├── public/          # Public files (entry point)
└── sql/            # Database schema and sample data
```

## Troubleshooting

1. **Database Connection Error**
   - Verify MySQL is running in XAMPP Control Panel
   - Check database credentials in `config/config.php`
   - Ensure the database 'library_management' exists

2. **Page Not Found Error**
   - Make sure the project is in the correct directory
   - Verify Apache is running in XAMPP Control Panel
   - Check if mod_rewrite is enabled in Apache

3. **Permission Issues**
   - Ensure the web server has read/write permissions to the project directory
   - For Windows: Run XAMPP as administrator
   - For Linux/Mac: Set appropriate permissions (e.g., `chmod 755` for directories)

## Features
- User Authentication (Admin/User roles)
- Book Management
- Author Management
- Category Management
- Borrowing System
- Search and Filter capabilities
- Responsive Design

## Security Notes
- Change the default admin password after first login
- Keep your XAMPP installation up to date
- Regularly backup your database
