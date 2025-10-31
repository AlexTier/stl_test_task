Запуск проекта:
1. docker-compose up -d --build
 	чек - docker-compose ps
2. docker-compose exec app mkdir -p bootstrap/cache storage/framework/sessions storage/framework/views storage/framework/cache
3. docker-compose exec app chown -R www-data:www-data bootstrap/cache storage
4. docker-compose exec app chmod -R 775 bootstrap/cache storage
5. docker-compose exec app composer install
6. docker-compose exec app php artisan key:generate
7. docker-compose exec app php artisan migrate --seed
	чек - docker-compose exec db mysql -u laravel -psecret slot_booking -e "SHOW TABLES;"
---------------------


проверить - http://localhost:8000

// Массив тестовых слотов
$slots = [
    [
        'name' => 'Утренняя доставка (08:00-10:00)',
        'capacity' => 10,
        'remaining' => 10,
        'created_at' => now(),
        'updated_at' => now(),
    ],
    [
        'name' => 'Дневная доставка (12:00-14:00)',
        'capacity' => 15,
        'remaining' => 15,
        'created_at' => now(),
        'updated_at' => now(),
    ],
    [
        'name' => 'Вечерняя доставка (18:00-20:00)',
        'capacity' => 8,
        'remaining' => 8,
        'created_at' => now(),
        'updated_at' => now(),
    ],
    [
        'name' => 'Склад А - окно 1',
        'capacity' => 5,
        'remaining' => 5,
        'created_at' => now(),
        'updated_at' => now(),
    ],
    [
        'name' => 'Склад А - окно 2',
        'capacity' => 5,
        'remaining' => 0, // Этот слот полностью занят
        'created_at' => now(),
        'updated_at' => now(),
    ],
];