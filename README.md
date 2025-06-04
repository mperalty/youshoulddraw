# You Should Draw

You Should Draw is a small PHP application that generates random character drawing prompts. It pulls information from a MySQL or SQLite database and combines different options to present unique ideas every time you click **Next Idea**. A simple CRUD interface is included so you can add or edit the options available.

## Requirements

* PHP 7.x or later with PDO and either the MySQL or SQLite extensions
* MySQL server or SQLite

## Database Schema

Two tables are required: `drawoptions` which stores the values used to build prompts, and `adminuser` for a simple password protected CRUD page.

Example schema:

```sql
CREATE TABLE `drawoptions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `type` varchar(50) NOT NULL,
  PRIMARY KEY (`id`)
);

CREATE TABLE `adminuser` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `password` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
);
```

The values in `drawoptions.type` correspond to options like `Base Class`, `Major Feature`, `Accessories`, `Emotion` and `Pet`.

## Database Connection

Create a file `includes/dbcon.php` in the project root that provides a `getPDO()` function returning a PDO connection. For a MySQL setup:

```php
<?php
function getPDO() {
    $host = getenv('YSD_DB_HOST') ?: 'localhost';
    $user = getenv('YSD_DB_USER');
    $pass = getenv('YSD_DB_PASS');
    $db   = getenv('YSD_DB_NAME');
    return new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
}
```

To use SQLite instead:

```php
<?php
function getPDO() {
    $path = getenv('YSD_DB_PATH') ?: __DIR__ . '/../ysd.sqlite';
    return new PDO('sqlite:' . $path);
}
```

Environment variables (`YSD_DB_HOST`, `YSD_DB_USER`, `YSD_DB_PASS`, `YSD_DB_NAME` or `YSD_DB_PATH`) keep credentials out of the codebase.

## Usage

1. Place the project files in a directory served by PHP.
2. Start a development server from the project root:
   ```bash
   php -S localhost:8000
   ```
3. Navigate to `http://localhost:8000/index.php` in your browser to generate drawing prompts.

The administrative tool for modifying options is `crud.php` and can be accessed the same way when you need to add or change prompt choices.
