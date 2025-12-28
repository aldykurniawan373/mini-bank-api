# Mini Bank Simpan-Pinjam

## Backend Setup (Laravel)

cd app-mini-bank-be
composer install
cp .env.example .env
php artisan key:generate

# edit .env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=mini_bank
DB_USERNAME=root
DB_PASSWORD=
QUEUE_CONNECTION=database

php artisan migrate
php artisan db:seed
php artisan storage:link
php artisan serve

## Queue Worker (WAJIB)

cd app-mini-bank-be
php artisan queue:work

## Akun Dummy

Admin (Teller)
email    : admin@bank.test
password : admin123

Pimpinan
email    : pimpinan@bank.test
password : admin123
