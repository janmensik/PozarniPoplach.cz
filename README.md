**PozarniPoplach.cz**

- **Description**: : Lightweight PHP web application for alarm dispatch and portal functions, using Smarty templates and a small custom MVC-like structure.
- **Repository**: `https://github.com/janmensik/PozarniPoplach.cz`

**Requirements**
- **PHP**: 7.4+ (verify with `php -v`).
- **Composer**: dependency management (`composer install`).
- **Web server**: Apache / Nginx or built-in PHP server for development.
- **Database**: MySQL/MariaDB (used by application models).

**Development notes & conventions**
- Models extend `lib/class.Modul.php` for DB access.
- Authentication and permissions use `include/class.User.php` and Casbin integration as initialized in `index.php`.
- Templates use Smarty. Assignments are done in PHP and passed to the templates in `tpl/`.
- Session storage: keep only primitives (user ID) in `$_SESSION`, do not serialize objects with closures/resources.

**Testing & CI**
- No project-wide test harness is included by default. Add PHPUnit or Playwright targets if you wish to add automated tests.

**Contributing**
- Create feature branches from `main`.
- Do not commit secrets. If you find secrets in the repo history, notify repo admins so the key can be rotated and history cleaned.

**Contact / Maintainer**
- Maintainer: `janmensik` (GitHub)

**License**
- This repository is licensed under the Creative Commons Attribution-NonCommercial-ShareAlike 4.0 International license (CC BY-NC-SA 4.0).
- See the `LICENSE` file in the repository root for the full license deed and notes.
- SPDX identifier: `CC-BY-NC-SA-4.0`.
