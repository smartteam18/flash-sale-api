<?php

class Database
{
    private static $instance;
    private $pdo;
    
    private function __construct()
    {
        $dir = __DIR__ . '/../database';
        if (!is_dir($dir)) mkdir($dir, 0777, true);
        
        $file = $dir . '/flash_sale.db';
        $new = !file_exists($file);
        
        $this->pdo = new PDO('sqlite:' . $file);
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->exec('PRAGMA journal_mode=WAL'); // Better concurrency
        
        if ($new) $this->migrate();
    }
    
    public static function getInstance()
    {
        if (!self::$instance) self::$instance = new self();
        return self::$instance->pdo;
    }
    
    private function migrate()
    {
        $this->pdo->exec('
            CREATE TABLE products (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                price DECIMAL(10,2) NOT NULL,
                flash_price DECIMAL(10,2),
                stock INTEGER NOT NULL DEFAULT 0
            );
            
            CREATE TABLE orders (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                order_number TEXT UNIQUE NOT NULL,
                total DECIMAL(10,2) NOT NULL,
                status TEXT DEFAULT "pending",
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );
            
            CREATE TABLE order_items (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                order_id INTEGER NOT NULL,
                product_id INTEGER NOT NULL,
                quantity INTEGER NOT NULL,
                price DECIMAL(10,2) NOT NULL,
                FOREIGN KEY (order_id) REFERENCES orders(id),
                FOREIGN KEY (product_id) REFERENCES products(id)
            );
            
            INSERT INTO products (name, price, flash_price, stock) 
            VALUES ("Flash Sale Item", 1000.00, 199.00, 10);
        ');
    }
    
    public function __call($method, $args)
    {
        return $this->pdo->$method(...$args);
    }
}