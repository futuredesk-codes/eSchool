# 🏫 eSchool Management System

A comprehensive School Management System built with Laravel 12, designed to streamline educational institution operations and enhance communication between administrators, teachers, students, and parents.

## 📚 Features

### 👨‍💼 Admin Features
- **Dashboard** - Complete overview of school statistics
- **Student Management** - Add, edit, view student profiles
- **Teacher Management** - Manage teaching staff
- **Class Management** - Create and manage classes and sections
- **Subject Management** - Add subjects and assign to classes
- **Fee Management** - Track fee payments and generate reports
- **Attendance System** - Monitor student attendance
- **Examination System** - Create exams and manage results
- **Announcement System** - Send notifications to all users
- **Report Generation** - Various reports for analysis

### 👨‍🏫 Teacher Features
- **Class Timetable** - View teaching schedule
- **Attendance Management** - Mark student attendance
- **Assignment Management** - Create and manage assignments
- **Grade Management** - Enter and update student grades
- **Student Progress** - Track individual student performance
- **Communication** - Send messages to students and parents

### 👨‍🎓 Student Features
- **Personal Dashboard** - View personal information and stats
- **Attendance History** - Check attendance records
- **Assignment Submission** - Submit assignments online
- **Grade Reports** - View exam results and progress
- **Timetable** - Access class schedule
- **Announcements** - Receive school notifications

### 👨‍👩‍👧‍👦 Parent Features
- **Child Progress Monitoring** - Track children's academic performance
- **Attendance Reports** - View attendance history
- **Fee Payment Status** - Check payment records
- **Communication** - Receive updates from teachers
- **Assignment Tracking** - Monitor homework completion

## 🛠️ Technology Stack

- **Backend**: Laravel 12 (PHP 8.3+)
- **Database**: MySQL
- **Frontend**: Blade Templates, Bootstrap, jQuery
- **Authentication**: Laravel Sanctum
- **File Storage**: Laravel Storage
- **Email**: SMTP Support
- **Permissions**: Spatie Laravel Permission

## 📋 Requirements

- PHP 8.3 or higher
- Composer
- MySQL 5.7+ or MariaDB
- Apache/Nginx Web Server
- Node.js & NPM (for asset compilation)

## 🚀 Installation

### Local Development Setup

1. **Clone the repository**
   ```bash
   git clone <repository-url>
   cd eschool-v12
   ```

2. **Install PHP dependencies**
   ```bash
   composer install
   ```

3. **Install JavaScript dependencies**
   ```bash
   npm install
   ```

4. **Environment Configuration**
   ```bash
   cp .env.example .env
   ```
   
5. **Edit .env file with your database credentials**
   ```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=eschool
   DB_USERNAME=your_username
   DB_PASSWORD=your_password
   ```

6. **Generate application key**
   ```bash
   php artisan key:generate
   ```

7. **Run database migrations**
   ```bash
   php artisan migrate
   ```

8. **Seed the database (optional)**
   ```bash
   php artisan db:seed
   ```

9. **Create storage symlink**
   ```bash
   php artisan storage:link
   ```

10. **Start development server**
    ```bash
    php artisan serve
    ```

## 🌐 Production Deployment

### Quick Deployment
Use the included deployment scripts for easy production setup:

```bash
# For complete upload and replacement
./complete_upload.sh

# For backup before deployment
./backup_old_code.sh
```

### Manual Deployment
1. Upload files to your web server
2. Configure web server to point to `public` directory
3. Set up environment variables
4. Run production commands:
   ```bash
   composer install --optimize-autoloader --no-dev
   php artisan key:generate
   php artisan migrate --force
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   ```

## 📖 Usage

### Default Login Credentials
After seeding the database, you can use these default credentials:

- **Super Admin**: 
  - Email: `admin@admin.com`
  - Password: `password`

### User Roles
The system supports multiple user roles:
- **Super Admin** - Full system access
- **Admin** - School administration
- **Teacher** - Teaching staff
- **Student** - Students
- **Parent** - Student guardians

## 🔧 Configuration

### File Upload Settings
Configure file upload limits in `.env`:
```env
MAX_FILE_SIZE=10240
ALLOWED_IMAGE_TYPES=jpg,jpeg,png,gif
ALLOWED_VIDEO_TYPES=mp4,avi,mov,wmv
ALLOWED_DOCUMENT_TYPES=pdf,doc,docx,xls,xlsx,ppt,pptx
```

### Timezone Configuration
```env
TIMEZONE=Asia/Kolkata
```

### Email Configuration
```env
MAIL_MAILER=smtp
MAIL_HOST=your_smtp_host
MAIL_PORT=587
MAIL_USERNAME=your_email
MAIL_PASSWORD=your_password
MAIL_ENCRYPTION=tls
```

## 📁 Project Structure

```
eschool-v12/
├── app/
│   ├── Http/Controllers/      # Application controllers
│   ├── Models/               # Eloquent models
│   ├── Services/             # Business logic services
│   └── Helpers/              # Helper functions
├── database/
│   ├── migrations/           # Database migrations
│   └── seeders/             # Database seeders
├── public/                   # Web accessible files
├── resources/
│   ├── views/               # Blade templates
│   ├── css/                 # Stylesheets
│   └── js/                  # JavaScript files
└── routes/                  # Application routes
```

## 🔒 Security Features

- CSRF Protection
- SQL Injection Prevention
- XSS Protection
- Role-based Access Control
- Secure Password Hashing
- Session Management

## 📊 API Documentation

The system includes RESTful APIs for mobile app integration:
- Student API endpoints
- Parent API endpoints
- Authentication APIs

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add some amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## 📝 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 🆘 Support

For support and questions:
- Create an issue in this repository
- Contact: [support email]
- Documentation: Check the `DEPLOYMENT_GUIDE.md` for detailed deployment instructions

## 🙏 Acknowledgments

- Laravel Framework
- Bootstrap CSS Framework
- Chart.js for analytics
- All contributors and the open-source community

---

**Made with ❤️ for Educational Institutions**

> This system is designed to make school management efficient and communication seamless between all stakeholders in the education process.
# eschool
