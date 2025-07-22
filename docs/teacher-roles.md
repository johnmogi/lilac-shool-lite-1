# Teacher Role Management System

## Overview
This document outlines the teacher role management system in School Manager Lite, which automatically assigns and manages teacher-specific capabilities and roles within WordPress.

## Features
- Automatic role assignment on user registration and updates
- Support for manual role changes in WordPress admin
- Integration with user imports
- Batch update functionality for existing teachers
- Comprehensive logging for debugging

## Implementation Details

### Core Files
- `includes/class-teacher-roles.php` - Main class handling role management

### Hooks Used
- `user_register` - Triggered when a new user is registered
- `profile_update` - Triggered when a user profile is updated
- `set_user_role` - Triggered when a user's role is changed
- `school_manager_after_import_user` - Custom hook for user imports

### Roles and Capabilities

#### Primary Roles
- `wdm_instructor` - Main instructor role
- `teacher` - Standard WordPress teacher role

#### Capabilities Added
- `school_teacher` - General teacher capability
- `ld_instructor` - LearnDash instructor capability
- `manage_groups` - Ability to manage groups
- `manage_courses` - Ability to manage courses

## Usage

### Automatic Assignment
When a user is created or updated with the `is_teacher` or `teacher` meta field set to `1`, the system will automatically assign the appropriate roles and capabilities.

### Manual Assignment
To manually assign teacher capabilities to a user:

```php
$user = get_user_by('id', $user_id);
$user->add_role('wdm_instructor');
$user->add_cap('school_teacher');
$user->add_cap('ld_instructor');
$user->add_cap('manage_groups');
$user->add_cap('manage_courses');
```

### Batch Update Existing Teachers
To update all existing teachers with the correct roles and capabilities:

```php
// Run this once
School_Manager_Teacher_Roles::update_existing_teachers();
```

## Integration with Imports
When importing users, include the `is_teacher` field in your import data:

```php
$user_data = array(
    // ... other user data ...
    'is_teacher' => '1'  // or 'teacher' => '1'
);
do_action('school_manager_after_import_user', $user_id, $user_data);
```

## Troubleshooting

### Checking User Roles and Capabilities
```php
// Get user object
$user = get_user_by('id', $user_id);

// Check roles
var_dump($user->roles);

// Check specific capabilities
var_dump($user->has_cap('school_teacher'));
var_dump($user->has_cap('ld_instructor'));
```

### Database Verification
```sql
-- Check user capabilities
SELECT * FROM {table_prefix}usermeta 
WHERE user_id = [USER_ID] 
AND meta_key IN ('wp_capabilities', 'school_teacher', 'ld_instructor');

-- Find all teachers
SELECT user_id, meta_value 
FROM {table_prefix}usermeta 
WHERE meta_key = 'wp_capabilities' 
AND meta_value LIKE '%wdm_instructor%';
```

## Logging
Debug information is logged to the WordPress debug log when `WP_DEBUG` is enabled. Look for entries prefixed with `[School Manager]`.

## Version History
- 1.0.0 - Initial implementation

---
*Documentation generated on 2025-07-21*
