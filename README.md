# Zuwad Plugin

A comprehensive WordPress plugin for educational institution management, featuring role-based access control, student management, teacher scheduling, and WhatsApp integration. Made by Afifi

## Description

Zuwad Plugin is a powerful WordPress plugin designed for educational institutions. It provides a complete solution for managing students, teachers, and supervisors with features like:

- Custom user roles (Student, Teacher, Supervisor)
- Student management and scheduling
- Teacher calendar and scheduling
- WhatsApp integration for notifications
- Payment tracking
- Student reports and progress tracking
- Supervisor dashboard
- PDF generation capabilities

## Features

- **User Management**
  - Custom roles: Student, Teacher, Supervisor
  - Role-specific dashboards
  - Custom user fields

- **Scheduling System**
  - Teacher calendar management
  - Student schedule tracking
  - Class scheduling

- **Communication**
  - WhatsApp integration
  - Automated notifications
  - Report sharing

- **Reporting**
  - Student progress reports
  - PDF report generation
  - Image upload support

- **Payment System**
  - Payment tracking
  - Payment history
  - Payment status management

## Installation

1. Download the plugin zip file
2. Go to WordPress admin panel > Plugins > Add New
3. Click "Upload Plugin" and select the downloaded zip file
4. Activate the plugin
5. Configure the plugin settings

## Requirements

- WordPress 5.0 or higher
- PHP 7.4 or higher
- MySQL 5.6 or higher

## Dependencies

The plugin uses the following external libraries:
- FullCalendar (v5.10.1)
- jsPDF (v2.5.1)
- html2canvas (v1.4.1)
- Lightbox2 (v2.11.3)
- Flatpickr
- SweetAlert2 (v11)

## Configuration

1. After activation, go to the plugin settings page
2. Configure WhatsApp integration settings
3. Set up user roles and permissions
4. Configure payment settings

## Usage

### For Students
- Access student dashboard
- View schedules
- Submit reports
- Track progress

### For Teachers
- Manage calendar
- Schedule classes
- Generate reports
- Send notifications

### For Supervisors
- Monitor student progress
- Manage teachers
- Generate reports
- Track payments

## Development

### Folder Structure
```
zuwad-plugin/
├── assets/         # CSS, JS, images
├── fonts/          # Custom fonts
├── includes/       # Core PHP classes/functions
├── templates/      # Template files
├── uninstall.php   # Uninstall script
└── zuwad-plugin.php # Main plugin file
```

### Contributing
1. Fork the repository
2. Create your feature branch
3. Commit your changes
4. Push to the branch
5. Create a Pull Request

## Security

- All AJAX requests are protected with nonces
- Input validation and sanitization
- Role-based access control
- Secure file upload handling

## Support

For support, please:
1. Check the documentation
2. Search existing issues
3. Create a new issue if needed

## License

This plugin is licensed under the GPL v2 or later.

## Credits

- Developed by [Afifi ]
- WordPress Plugin Boilerplate
- Various open-source libraries (see Dependencies section)

## Changelog

### 1.3.3
- Initial release
- Basic functionality implementation
- Role management
- WhatsApp integration

## WordPress Plugin Repository

This plugin is available on the WordPress Plugin Repository: [Link to be added]

## GitHub Repository

[Link to be added] 