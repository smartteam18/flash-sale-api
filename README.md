# Flash Sale API

Hasil pengerjaan FULLSTACK ENGINEER ASSESSMENT TEST - Fomotoko

---

## Tentang

Ini hasil pengerjaan 2 tugas dari assessment:
1. **API online store** - buat flash sale + handle race condition
2. **Grid navigation** - nyari kemungkinan lokasi item


---

## Cara Clone & Jalankan

```bash
git clone https://github.com/smartteam18/flash-sale-api.git
cd flash-sale-api
composer install

### Task 1. Online Store API
Menjalankan Server

```bash
php -S localhost:8000 -t public/

Setelah itu buka:

Browser: http://localhost:8000

atau pakai Postman.

## Endpoint

Method	    Endpoint	        Fungsi
GET	    /api/products	    Lihat semua produk
GET	    /api/products/{id}	Lihat detail produk
POST	/api/orders	        Beli produk (flash sale)
GET	    /api/orders/{id}	Lihat detail order

Contoh Penggunaan (curl)

## Lihat semua produk

```bash
curl http://localhost:8000/api/products

##Beli produk

```bash
curl -X POST http://localhost:8000/api/orders \
  -H "Content-Type: application/json" \
  -d '{"product_id":1,"quantity":1}'

## Lihat detail order

```bash
curl http://localhost:8000/api/orders/1

## Test Race Condition

```bash
php tests/RaceConditionTest.php

## Output

Stock reset to 10
=== Race Condition Test ===
Initial stock: 10
Sending 15 concurrent requests...

--- Results ---
Successful: 10
Failed: 5
Final stock: 0
Expected stock: 0

✅ PASS: Stock is consistent and never negative


### Task 2. Grid Navigation

## Jalankan

```bash
php grid.php

## Grid yang dipakai

# # # # # # # #
# . . . . . . #
# . # # # . # #
# . . . # . # #
# X # . . . . #
# # # # # # # #


# = tembok

. = jalan

X = posisi awal

## Output

Kemungkinan lokasi item:
1. (4, 1) -> Up 1, Right 2, Down 1
2. (4, 0) -> Up 2, Right 2, Down 2
3. (3, 2) -> Up 3, Right 1, Down 3

Grid dengan tanda $:

# # # # # # # #
# . . . $ . . #
# . # # # . # #
# . $ . # . # #
# X # . . . . #
# # # # # # # #

Tanda $ di grid itu kemungkinan lokasi item. Ada 3 titik kemungkinan.

### Catatan
Database pakai SQLite, jadi tidak perlu install MySQL

Pertama kali jalan, database bakal otomatis terbentuk

Kalo mau reset stok, tinggal jalankan test lagi (otomatis reset ke 10)


### Struktur Folder

flash-sale-api/
├── public/
│   └── index.php               # API endpoint
├── src/
│   └── Database.php            # Koneksi + schema database
├── tests/
│   └── RaceConditionTest.php   # Test race condition
├── grid.php                    # Grid navigation
├── composer.json
└── README.md








