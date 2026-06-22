<?php

require_once __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(
    dirname(__DIR__)
);

$dotenv->safeLoad();

date_default_timezone_set('Asia/Dubai');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

define(
    'UPLOAD_DIR',
    __DIR__ . '/../assets/uploads/photos/'
);

define(
    'UPLOAD_URL',
    'assets/uploads/photos/'
);

define(
    'BASE_URL',
    $_ENV['BASE_URL'] ?? 'http://localhost/visitor_system/'
);

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

        }

        catch(PDOException $e){

            die(
                "Database connection failed: "
                .
                $e->getMessage()
            );

        }

    }

    return $pdo;
}