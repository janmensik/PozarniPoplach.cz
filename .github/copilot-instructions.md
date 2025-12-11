# Copilot Instructions for pozarnipoplach.cz

## Project Overview

This is a PHP web application.

## Key Components

- **index.php**: Main entry point, handles session, user authentication, routing, and global assignments for Smarty.
- **lib/class.Modul.php**: Base class for data models, provides database access and caching.
- **lib/class.Database.php**: Base class for SQL database connection and queries.
- **include/class.User.php**: User model, extends `Modul`, manages authentication, permissions (via Casbin), and user data.
- **lib/class.AppData.php**: Object for application-wide data, used for caching and global settings.
- **include/routes.php**: Defines application routes using Bramus Router, maps URLs to PHP
- **tpl/**: Smarty templates for UI, including `inc.header.html` for layout and navigation.
- **ui/application.js**: Main JavaScript for UI interactions, including date pickers and modals.
- **ui/application.css**: Custom CSS, extends AdminLTE and handles sidebar/menu quirks.

## Developer Workflows

- **Dependencies**: Managed via Composer (`composer.json`) for PHP and npm (`package.json`) for JS (Playwright for testing).
- **Templating**: Use Smarty variables and modifiers. Assignments are made in PHP and passed to templates.
- **Session Handling**: Only store primitive user data (e.g., user ID) in `$_SESSION`. Do not serialize objects containing closures or resources.
- **Routing**: Uses Bramus Router (`/include/routes.php`). Route handlers are defined in PHP files under `/view/page/`.
- **Access Control**: Casbin is initialized in `index.php` and injected into models as needed.

## Project-Specific Conventions

- **Model Inheritance**: All data models extend `Modul` for consistent DB access.
- **User Object**: Do not serialize the full `$User` object into the session; store only the user ID and reload as needed.
- **Date Handling**: Uses Moment.js and daterangepicker for date selection in the UI. Date formats are locale-specific (e.g., `DD. MM. YYYY`). For backend, use PHP's `DateTime` class for date manipulation with Nette\Utils\DateTime.
- **No Central Build/Test Scripts**: No custom build or test scripts are defined; use Composer and npm as needed.
- **Validation**: Use PHP's built-in validation functions and custom validation methods in models and Nette\Utils\Validators for common validation like email format etc. Avoid complex validation logic in templates.



## Examples

- **Adding a new page**: Create a PHP file in `/view/page/`, add a route in `/include/routes.php`, and a template in `/tpl/`.
- **Debugging**: Use Smarty's debug console and PHP error logs. Enable debug mode via `$_ENV['DEBUGGING']`.

---

**Feedback Request:**