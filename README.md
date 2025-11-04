# Portfolio System - Activity 3

A PHP + PostgreSQL portfolio system with public resume display and secure editing capabilities.

## Features

- **Public Resume Display**: Resume is publicly visible by default
- **Login to Edit**: Secure login system to access editing features
- **Dashboard**: Edit resume information with a comprehensive form
- **Responsive Design**: Clean, modern CSS styling
- **Form Validation**: Client and server-side validation
- **CRUD Operations**: Create, Read, Update functionality for user data

## Setup Instructions

### 1. Database Setup

1. Install PostgreSQL on your system
2. Create a new database:
   ```sql
   CREATE DATABASE portfolio_db;
   ```

3. Run the SQL script to create tables and insert sample data:
   ```bash
   psql -U postgres -d portfolio_db -f database_setup.sql
   ```

### 2. Configuration

1. Update `config.php` with your PostgreSQL credentials:
   ```php
   $host = 'localhost';
   $dbname = 'portfolio_db';
   $username = 'postgres';
   $password = 'your_password'; // Change this
   ```

### 3. Web Server Setup

1. Place all files in your web server directory (e.g., `htdocs` for XAMPP)
2. Ensure PHP has PostgreSQL extension enabled
3. Start your web server

### 4. Access the Application

1. Open your browser and navigate to `http://localhost/websys_act3/`
2. **Public View**: Resume is displayed publicly by default
3. **Login to Edit**: Click "🔐 Login to Edit" button
4. **Login Credentials**:
   - Username: `admin`
   - Password: `1234`

## File Structure

- `index.php` - Entry point, redirects to public resume
- `public_resume.php` - **Main page** - Public resume display with login button
- `login.php` - User authentication page
- `dashboard.php` - Resume editing interface (requires login)
- `config.php` - Database configuration
- `database_setup.sql` - Database schema and sample data

## Usage

### Public View (Default):
1. **Resume is publicly visible** - No login required
2. **View all resume information** - Clean, professional display
3. **Login button** - Click "🔐 Login to Edit" to access editing

### For Editing:
1. **Click "🔐 Login to Edit"** button on the public resume
2. **Login with credentials**: `admin` / `1234`
3. **Edit resume information** in the dashboard
4. **Save changes** to update the database
5. **Logout** to return to public view

## Security Features

- Password hashing using PHP's `password_verify()`
- SQL injection prevention with prepared statements
- Input validation and sanitization
- Session management for authentication

## Technologies Used

- **Backend**: PHP 7.4+
- **Database**: PostgreSQL
- **Frontend**: HTML5, CSS3, JavaScript
- **Security**: Password hashing, prepared statements

## Requirements Met

✅ Login authentication connected to PostgreSQL  
✅ Editable resume form (update user data)  
✅ Public access display page (read-only)  
✅ Basic layout styling with CSS  
✅ Form validation before saving changes  
✅ Responsive design for mobile devices  
✅ CRUD operations for user data management
