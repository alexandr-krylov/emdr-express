<?php

$db = new PDO('sqlite:' . __DIR__ . '/../db/database.sqlite');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// создаём таблицу если нет
$db->exec("
CREATE TABLE IF NOT EXISTS orders (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    invoice_id TEXT UNIQUE,
    email TEXT,
    amount INTEGER,
    status TEXT DEFAULT 'pending',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)");
