# Alter-Consult.com Website

A Drupal 11-based professional services website for Alter-Consult, offering Drupal solutions, digital consulting, and project management services.

## Requirements

- PHP 8.2 or higher
- Composer
- MySQL 5.7.8 or higher
- Drush 12 or higher

## Installation

1. Clone the repository:
```bash
git clone [repository-url]
cd alter-consult-website
```

2. Install dependencies:
```bash
composer install
```

3. Create a new database and configure settings:
```bash
cp web/sites/default/default.settings.php web/sites/default/settings.php
```

4. Install Drupal:
```bash
drush site:install --db-url=mysql://[user]:[password]@localhost/[database] --account-name=admin --account-pass=[password] --site-name="Alter-Consult" -y
```

5. Enable required modules:
```bash
drush en google_analytics token pathauto metatag -y
```

6. Enable the custom theme:
```bash
drush theme:enable alterconsult
drush config:set system.theme default alterconsult -y
```

## Development

1. Enable development modules:
```bash
composer require --dev drupal/devel
drush en devel -y
```

2. Clear cache after making changes:
```bash
drush cr
```

## Features

- Responsive design for mobile, tablet, and desktop
- Custom theme with brand colors and modern UI
- Service showcase with card layout
- Benefits carousel
- Contact form integration
- Google Analytics integration
- SEO optimization
- User account management

## Theme Structure

```
web/themes/custom/alterconsult/
├── css/
│   ├── style.css
│   └── responsive.css
├── js/
│   └── main.js
├── alterconsult.info.yml
└── alterconsult.libraries.yml
```

## Contributing

1. Create a feature branch
2. Make your changes
3. Submit a pull request

## License

This project is licensed under the GPL-2.0-or-later license.