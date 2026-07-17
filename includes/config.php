<?php

require_once __DIR__ . '/../vendor/autoload.php';

date_default_timezone_set('Asia/Dubai');
$dotenv = Dotenv\Dotenv::createImmutable(
    dirname(__DIR__)
);

$dotenv->safeLoad();

date_default_timezone_set('Asia/Dubai');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
define(
    'SITE_CODE',
    $_COOKIE['site_code'] ?? null
);
/*
|--------------------------------------------------------------------------
| Storage Paths
|--------------------------------------------------------------------------
|
| Local (XAMPP):
|   assets/uploads/
|
| Railway:
|   /data/storage/
|
*/

if (is_dir('/data/storage')) {

    define('STORAGE_ROOT', '/data/storage/');
    define('UPLOAD_URL', '/data/storage/');

} else {

    define(
        'STORAGE_ROOT',
        __DIR__ . '/../assets/uploads/'
    );

    define(
        'UPLOAD_URL',
        'assets/uploads/'
    );

}
define(
    'BASE_URL',
    $_ENV['BASE_URL'] ?? 'http://localhost/visitor_system/'
);
/*
|--------------------------------------------------------------------------
| Visitor Storage
|--------------------------------------------------------------------------
*/

define(
    'VISITOR_PHOTO_DIR',
    STORAGE_ROOT . 'visitors/photos/'
);

define(
    'VISITOR_DOCUMENT_DIR',
    STORAGE_ROOT . 'visitors/documents/'
);

/*
|--------------------------------------------------------------------------
| Employee Storage
|--------------------------------------------------------------------------
*/

define(
    'EMPLOYEE_PHOTO_DIR',
    STORAGE_ROOT . 'employees/photos/'
);

define(
    'EMPLOYEE_DOCUMENT_DIR',
    STORAGE_ROOT . 'employees/documents/'
);
$directories = [

    VISITOR_PHOTO_DIR,
    VISITOR_DOCUMENT_DIR,
    EMPLOYEE_PHOTO_DIR,
    EMPLOYEE_DOCUMENT_DIR

];

foreach ($directories as $dir) {

    if (!is_dir($dir)) {

        mkdir($dir, 0755, true);

    }

}
function getDB()
{
    static $pdo = null;

    if ($pdo === null) {

        try {

            $pdo = new PDO(

                "mysql:host={$_ENV['DB_HOST']};
                port={$_ENV['DB_PORT']};
                dbname={$_ENV['DB_NAME']};
                charset=utf8mb4",

                $_ENV['DB_USER'],

                $_ENV['DB_PASS'],

                [

                    PDO::ATTR_ERRMODE =>
                        PDO::ERRMODE_EXCEPTION,

                    PDO::ATTR_DEFAULT_FETCH_MODE =>
                        PDO::FETCH_ASSOC,

                    PDO::ATTR_EMULATE_PREPARES =>
                        false

                ]

            );
            $pdo->exec("SET time_zone = '+04:00'");
        } catch (PDOException $e) {

            die(
                "Database connection failed: "
                .
                $e->getMessage()
            );

        }

    }

    return $pdo;
}