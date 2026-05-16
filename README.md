**PozarniPoplach.cz**

- **Description**: Lightweight PHP web application for fire alarm dispatch and back-office portal functions. It serves as a real-time dashboard for fire stations and a management system for reservations.
- **Repository**: `https://github.com/janmensik/PozarniPoplach.cz`

## Key Subsystems

### 1. Fire Alarm Dispatch & Dashboard
- **IMAP Parsing**: Automatically imports dispatch emails from fire stations via `cron.emailimport.php`.
- **Structured Parsing**: Uses `Dispatch::parseDispatchHtml` to extract structured data (event type, location, GPS, vehicles) from HTML emails.
- **Real-time Dashboard**: A specialized view (`/alarm/dispatch`) with a countdown timer, automatic content refresh every 10 seconds, and audio alerts for incoming poplachy.
- **Mapping & Directions**: Integrates with Google Maps and Mapbox to show event locations, calculate driving directions, and provide static street views.

### 2. Back-Office Reservation System
- **Management**: Dashboard and tools for managing reservations, partners, pricelists, and gate codes.
- **Dashboard**: Provides a daily overview of occupancy, check-ins, check-outs, and gate code status.
- **Integration**: Likely shares infrastructure (DB, auth) with the alarm dispatch system.

## Technical Architecture

- **Backend**: Custom MVC-like structure in PHP 7.4+.
- **Models**: Business logic encapsulated in classes within `include/` (e.g., `Dispatch.php`, `User.php`, `Reservation.php`).
- **Routing**: Handled by `bramus/router` (defined in `include/routes.php`).
- **Templating**: Smarty 5 (`tpl/` directory) with custom plugins in `lib/smarty-plugins/`.
- **Auth & Permissions**: Custom `User` class integrated with **Casbin** for fine-grained access control (`include/acl.model.conf`, `include/acl.policy.csv`).
- **Database**: MySQL/MariaDB. Schema is managed via models and can be tracked in `changes.sql`.
- **Environment**: Configuration via `.env` files using `vlucas/phpdotenv`.

## Requirements
- **PHP**: 7.4+
- **Composer**: For dependency management (`composer install`).
- **Web server**: Apache (with `.htaccess`) or Nginx.
- **Database**: MySQL/MariaDB.
- **External APIs**: Google Maps, Mapbox, Brevo (for emails).

## Development Notes
- Models extend `lib/class.Modul.php` (aliased from `Janmensik\Jmlib\Modul`).
- Session storage: Primitives only (user ID), avoid serializing complex objects.
- Frontend: CSS/JS in `ui/`. Real-time logic for the dashboard is in `ui/alarm.js`.

## License
- This repository is licensed under the Creative Commons Attribution-NonCommercial-ShareAlike 4.0 International license (CC BY-NC-SA 4.0).
- SPDX identifier: `CC-BY-NC-SA-4.0`.
