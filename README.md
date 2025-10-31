# Slot Booking Service API

[![PHP](https://img.shields.io/badge/PHP-8.2+-777BB4?style=flat&logo=php&logoColor=white)](https://www.php.net/)
[![Laravel](https://img.shields.io/badge/Laravel-12-FF2D20?style=flat&logo=laravel&logoColor=white)](https://laravel.com/)
[![Docker](https://img.shields.io/badge/Docker-Ready-2496ED?style=flat&logo=docker&logoColor=white)](https://www.docker.com/)
[![Redis](https://img.shields.io/badge/Redis-7+-DC382D?style=flat&logo=redis&logoColor=white)](https://redis.io/)
[![MySQL](https://img.shields.io/badge/MySQL-8+-4479A1?style=flat&logo=mysql&logoColor=white)](https://www.mysql.com/)
[![License](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)

REST API сервис для бронирования временных слотов с поддержкой временных холдов, кеширования и защитой от оверсела.

## Возможности

- Получение доступных слотов с кешированием (Redis)
- Создание временных холдов (5 минут)
- Подтверждение и отмена бронирований
- Идемпотентность запросов через `Idempotency-Key`
- Защита от race conditions (pessimistic locking)
- Защита от cache stampede
- Транзакционная безопасность

## Стек

- **PHP 8.2+**
- **Laravel 12**
- **MySQL 8+**
- **Redis 7+**
- **Docker & Docker Compose**
- **Nginx**

---

## Быстрый старт

### Предварительные требования

- [Docker Desktop](https://www.docker.com/products/docker-desktop/) (Windows/Mac) или Docker Engine (Linux)
- [Git](https://git-scm.com/downloads)

### 1. Клонирование репозитория

```bash
git clone https://github.com/your-username/slot-booking-service.git
cd slot-booking-service
```

### 2. Настройка окружения

Файл `.env` уже настроен для Docker. Проверьте основные параметры:

```env
DB_CONNECTION=mysql
DB_HOST=db
DB_PORT=3306
DB_DATABASE=slot_booking
DB_USERNAME=laravel
DB_PASSWORD=secret

CACHE_STORE=redis
REDIS_HOST=redis
REDIS_PORT=6379

SESSION_DRIVER=file
```

### 3. Запуск Docker-контейнеров

```bash
# Запустить все сервисы (PHP, MySQL, Redis, Nginx)
docker-compose up -d --build
```
### 4. Установка зависимостей

```bash
# Установить PHP-зависимости через Composer
docker-compose exec app composer install

# Создать необходимые директории
docker-compose exec app mkdir -p bootstrap/cache storage/framework/sessions storage/framework/views storage/framework/cache

# Установить права
docker-compose exec app chown -R www-data:www-data bootstrap/cache storage
docker-compose exec app chmod -R 775 bootstrap/cache storage
```

### 5. Настройка базы данных

```bash
# Сгенерировать ключ приложения
docker-compose exec app php artisan key:generate

# Запустить миграции и заполнить тестовыми данными
docker-compose exec app php artisan migrate --seed
```

### 6. Проверка работы

Откройте в браузере: [http://localhost:8000](http://localhost:8000)

Должен вернуться JSON:
```json
{"message":"Slot Booking Service"}
```

## API Endpoints

### Получить доступные слоты

```http
GET /api/slots/availability
```

**Ответ:**
```json
[
  {
    "slot_id": 1,
    "name": "Склад А - окно 1",
    "capacity": 10,
    "remaining": 10
  }
]
```

### Создать холд

```http
POST /api/slots/{id}/hold
Headers:
  Idempotency-Key: 550e8400-e29b-41d4-a716-446655440001
```

**Ответ:**
```json
{
  "id": 1,
  "slot_id": 1,
  "status": "held",
  "expires_at": "2025-10-30T00:25:00.000000Z"
}
```

### Подтвердить холд

```http
POST /api/holds/{id}/confirm
```

**Ответ:**
```json
{
  "id": 1,
  "status": "confirmed"
}
```

### Отменить холд

```http
DELETE /api/holds/{id}
```

**Ответ:**
```json
{
  "id": 1,
  "status": "cancelled"
}
```

---

## Тестирование

### Postman

1. Импортируйте коллекцию: `postman_collection.json`
2. Создайте окружение с переменной `base_url = http://localhost:8000`
3. Запустите тесты

в некоторых методах в ответе в Headers "X-Cache-Hit" показывает, был ли запрос из кеша

### PowerShell примеры

```powershell
# Получить доступные слоты
Invoke-RestMethod -Uri "http://localhost:8000/api/slots/availability"

# Создать холд
Invoke-RestMethod -Uri "http://localhost:8000/api/slots/1/hold" `
  -Method Post `
  -Headers @{"Idempotency-Key"="550e8400-e29b-41d4-a716-446655440001"}

# Подтвердить холд
Invoke-RestMethod -Uri "http://localhost:8000/api/holds/1/confirm" -Method Post

# Отменить холд
Invoke-RestMethod -Uri "http://localhost:8000/api/holds/1" -Method Delete
```

### cURL примеры (Linux/Mac)

```bash
# Получить доступные слоты
curl http://localhost:8000/api/slots/availability

# Создать холд
curl -X POST http://localhost:8000/api/slots/1/hold -H "Idempotency-Key: 550e8400-e29b-41d4-a716-446655440001"

# Подтвердить холд
curl -X POST http://localhost:8000/api/holds/1/confirm

# Отменить холд
curl -X DELETE http://localhost:8000/api/holds/1
```

### Работа с базой данных

```bash
# Пересоздать базу данных
docker-compose exec app php artisan migrate:fresh --seed

# Подключиться к MySQL
docker-compose exec db mysql -u laravel -psecret slot_booking

# Посмотреть таблицы
docker-compose exec db mysql -u laravel -psecret slot_booking -e "SHOW TABLES;"
```

### Работа с кешем

```bash
# Очистить кеш Redis
docker-compose exec redis redis-cli FLUSHALL

# Проверить ключи в Redis
docker-compose exec redis redis-cli KEYS '*'

# Очистить кеш Laravel
docker-compose exec app php artisan cache:clear
```
