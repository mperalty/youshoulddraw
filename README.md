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
  `theme` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`)
);

CREATE TABLE `adminuser` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `password` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
);

CREATE TABLE `generated_prompts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `base_class_id` int(11) NOT NULL,
  `major_feature_id` int(11) NOT NULL,
  `accessory1_id` int(11) DEFAULT NULL,
  `accessory2_id` int(11) DEFAULT NULL,
  `accessory3_id` int(11) DEFAULT NULL,
  `emotion_id` int(11) DEFAULT NULL,
  `pet_id` int(11) DEFAULT NULL,
  `prompt` text NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
);
```

The values in `drawoptions.type` correspond to options like `Base Class`, `Major Feature`, `Accessories`, `Emotion` and `Pet`.
The optional `theme` column lets you group options into sets (for example `Sci-Fi`, `Medieval` or `Cute`). Existing installations can add the column with:

```sql
ALTER TABLE `drawoptions` ADD `theme` varchar(50) DEFAULT NULL;
```

A SQL dump containing this schema along with a few starter rows can be found at
`includes/sample.sql`. Importing this file will set up the tables and populate
them with some example options and an admin account whose password is `admin`.

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

## API

A simple JSON endpoint is available at `/api/getidea`. It accepts the same query parameters as the form on `index.php` (for example `gender`, `emotion`, `pet`, `accessories` and `theme`). The response contains a single `prompt` field:

```bash
$ curl 'http://localhost:8000/api/getidea'
{"prompt":"You should draw ..."}
```

Each response also includes an `id` field which can be used to share the prompt:
`/share/<ID>` will display the stored text.

## Sharing Prompts

Whenever a prompt is generated (through the website or the API) it is stored in
the `generated_prompts` table. The ID of the saved row is returned to the
frontend so you can share a direct link such as:

```
http://localhost:8000/share/123
```

Opening that URL will show the exact text that was generated.

