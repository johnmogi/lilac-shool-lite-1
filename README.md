# School Manager Lite

A lightweight WordPress plugin for managing teachers, classes, students, and promo codes without LearnDash dependency.

## Description

School Manager Lite provides a complete solution for educational institutions to manage their teaching infrastructure. The plugin creates a role-based system where administrators can create teachers, assign them to classes, and generate promo codes for student enrollment.

## Features

- **Teacher Management**: Create and manage teachers with dedicated dashboard access
- **Class Management**: Create and assign classes to teachers
- **Student Management**: Track students enrolled in specific classes
- **Promo Code System**: Generate, distribute, and track promo codes for class enrollment
- **Admin Wizard**: Guided setup process for creating teachers, classes, and promo codes
- **Teacher Dashboard**: Custom WordPress admin experience for teachers to view their classes and students

## Requirements

- WordPress 5.6 or higher
- PHP 7.2 or higher
- MySQL 5.6 or higher

## Installation

1. Upload the `school-manager-lite` directory to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Navigate to the 'School Manager' menu item in the WordPress admin
4. Use the 'Setup Wizard' to get started quickly

## Core Components

### User Roles

- **school_teacher**: WordPress user role for teachers
- **student_private**: WordPress user role for enrolled students

### Database Tables

- **school_classes**: Stores class information including name, description, and teacher assignment
- **school_students**: Links students (WordPress users) to specific classes
- **school_promo_codes**: Manages promo codes, their usage, and association with classes

## Key Functions

### Teacher Management

- `create_teacher($data)`: Creates a new teacher (WordPress user with school_teacher role)
- `assign_teacher_role($user_id)`: Assigns the teacher role to an existing WordPress user
- `get_all_teachers()`: Retrieves all users with the teacher role
- `get_teacher($teacher_id)`: Gets specific teacher data

### Class Management

- `create_class($data)`: Creates a new class and assigns it to a teacher
- `get_teacher_classes($teacher_id)`: Retrieves all classes assigned to a specific teacher
- `get_class($class_id)`: Gets specific class data
- `update_class($class_id, $data)`: Updates class information

### Student Management

- `create_student($data)`: Creates a new student user
- `enroll_student($student_id, $class_id)`: Enrolls a student in a specific class
- `get_class_students($class_id)`: Retrieves all students enrolled in a specific class
- `get_teacher_students($teacher_id)`: Retrieves all students in a teacher's classes

### Promo Code Management

- `generate_promo_codes($data)`: Creates multiple promo codes with specified parameters
- `redeem_promo_code($code, $user_id)`: Processes promo code redemption and enrolls the user
- `get_promo_codes($args)`: Retrieves promo codes based on various filters
- `verify_promo_code($code)`: Validates if a promo code exists and is usable

### Admin Wizard

- Multi-step process for setting up the school management system
- Guides administrators through teacher creation, class setup, and promo code generation
- Each step has both view and handler methods for rendering UI and processing form submissions

## Shortcodes

- `[school_promo_code_form]`: Displays a form for students to redeem promo codes

## Template Structure

- **Admin Templates**: Located in `templates/admin/` directory
  - `teacher-dashboard.php`: Main dashboard for teachers
  - `teacher-classes.php`: Displays teacher's classes
  - `teacher-students.php`: Displays students in teacher's classes
  - `teacher-promo-codes.php`: Manages promo codes for teacher's classes

- **Widget Templates**: Located in `templates/admin/widgets/` directory
  - `classes-widget.php`: Dashboard widget showing teacher's classes
  - `students-widget.php`: Dashboard widget showing recent students
  - `promo-codes-widget.php`: Dashboard widget showing promo code status

## Hooks and Filters

The plugin provides various hooks for extending functionality:

- `school_manager_after_teacher_create`: Fires after a teacher is created
- `school_manager_after_class_create`: Fires after a class is created
- `school_manager_after_student_enroll`: Fires after a student is enrolled in a class
- `school_manager_before_promo_code_redeem`: Fires before a promo code is redeemed

## Future Enhancements

- LearnDash integration via syncing layer
- Enhanced reporting and analytics
- Email notifications for enrollment events
- Advanced student progress tracking

## License

This plugin is licensed under the GPL v2 or later.

---

For support or customization requests, please contact the plugin author.
# school-manager-lite-1
# lilac-shool-lite-1
