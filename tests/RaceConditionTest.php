<?php

require_once __DIR__ . '/../src/Database.php';

class RaceConditionTest
{
    // TODO: bikin URL configurable via argumen CLI
    private $url = 'http://localhost:8000/api/orders';
    
    public function run()
    {
        // reset stok dulu biar hasil tes konsisten
        $this->resetStock();
        
        echo "=== Race Condition Test ===\n";
        echo "Initial stock: " . $this->getStock() . "\n";
        echo "Sending 15 concurrent requests...\n\n";
        
        $mh = curl_multi_init();
        $handles = [];
        
        // kirim 15 request paralel
        for ($i = 0; $i < 15; $i++) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $this->url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['product_id' => 1, 'quantity' => 1]));
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 5);
            curl_multi_add_handle($mh, $ch);
            $handles[] = $ch;
        }
        
        // jalankan semua request
        $running = null;
        do {
            curl_multi_exec($mh, $running);
            curl_multi_select($mh);
        } while ($running > 0);
        
        $success = 0;
        $failed = 0;
        
        // hitung hasil sukses/gagal
        foreach ($handles as $ch) {
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            if ($httpCode === 201) {
                $success++;
            } else {
                $failed++;
                // bisa tambahin debug print response body kalau perlu
            }
            curl_multi_remove_handle($mh, $ch);
            curl_close($ch);
        }
        curl_multi_close($mh);
        
        $finalStock = $this->getStock();
        
        echo "--- Results ---\n";
        echo "Successful: $success\n";
        echo "Failed: $failed\n";
        echo "Final stock: $finalStock\n";
        echo "Expected stock: " . max(0, 10 - $success) . "\n\n";
        
        // cek konsistensi stok
        if ($finalStock >= 0 && $finalStock === max(0, 10 - $success)) {
            echo "✅ PASS: Stock is consistent and never negative\n";
        } else {
            echo "❌ FAIL: Stock inconsistency detected!\n";
        }
    }
    
    private function getStock()
    {
        $db = Database::getInstance();
        $stmt = $db->query('SELECT stock FROM products WHERE id = 1');
        return (int) $stmt->fetchColumn();
    }
    
    private function resetStock()
    {
        $db = Database::getInstance();
        // reset stok ke 10 (sementara hardcoded ke product_id=1)
        $db->exec("UPDATE products SET stock = 10 WHERE id = 1");
        // bersihin order lama biar tes bisa diulang
        $db->exec("DELETE FROM order_items");
        $db->exec("DELETE FROM orders");
        echo "Stock reset to 10\n";
    }
}

// jalanin cuma kalau via CLI
if (php_sapi_name() === 'cli') {
    (new RaceConditionTest())->run();
}
