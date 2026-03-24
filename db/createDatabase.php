<?php

$dbFile = __DIR__ . '/database.sqlite';
$db = new PDO('sqlite:' . $dbFile);

$db->exec("
    CREATE TABLE IF NOT EXISTS links (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        email TEXT NOT NULL,
        link TEXT NOT NULL,
        created_at TEXT,
        updated_at TEXT,
        read_at TEXT
    )
");