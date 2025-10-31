# Полный набор curl-примеров для тестирования API

## Базовые операции

### 1. Получить доступные слоты

```bash
curl http://localhost:8000/api/slots/availability
```

**Ожидаемый ответ (200 OK):**
```json
[
  {
    "slot_id": 1,
    "name": "Склад А - окно 1",
    "capacity": 10,
    "remaining": 10
  },
  {
    "slot_id": 2,
    "name": "Склад А - окно 2",
    "capacity": 5,
    "remaining": 5
  }
]
```
**Проверка заголовков:**
```bash
curl -I http://localhost:8000/api/slots/availability
```
Должны быть заголовки:
- `X-Cache-Hit: false` (первый запрос)
- `X-Data-Source: database`

---

### 2. Проверка кеширования (повторный запрос)

```bash
# Первый запрос (из БД)
curl -v http://localhost:8000/api/slots/availability 2>&1 | grep "X-Cache-Hit"

# Второй запрос сразу после первого (из кеша)
curl -v http://localhost:8000/api/slots/availability 2>&1 | grep "X-Cache-Hit"
```

**Ожидаемый результат:**
- Первый: `X-Cache-Hit: false`
- Второй: `X-Cache-Hit: true`

---

## Создание холдов

### 3. Создать холд (успешно)

```bash
curl -X POST http://localhost:8000/api/slots/1/hold -H "Idempotency-Key: 550e8400-e29b-41d4-a716-446655440001"
```

**Ожидаемый ответ (201 Created):**
```json
{
  "id": 1,
  "slot_id": 1,
  "idempotency_key": "550e8400-e29b-41d4-a716-446655440001",
  "status": "held",
  "expires_at": "2025-10-30T01:05:00.000000Z",
  "created_at": "2025-10-30T01:00:00.000000Z",
  "updated_at": "2025-10-30T01:00:00.000000Z"
}
```

---

### 4. Идемпотентность (повторный запрос с тем же ключом)

```bash
# Повторить запрос с тем же Idempotency-Key
curl -X POST http://localhost:8000/api/slots/1/hold -H "Idempotency-Key: 550e8400-e29b-41d4-a716-446655440001"
```

**Ожидаемый ответ (200 OK, не 201):**
```json
{
  "id": 1,
  "slot_id": 1,
  "idempotency_key": "550e8400-e29b-41d4-a716-446655440001",
  "status": "held",
  "expires_at": "2025-10-30T01:05:00.000000Z",
  "created_at": "2025-10-30T01:00:00.000000Z",
  "updated_at": "2025-10-30T01:00:00.000000Z"
}
```

**Обратите внимание:**
- Тот же `id: 1`
- Новый холд НЕ создан

---

### 5. Создать холд без Idempotency-Key (ошибка)

```bash
curl -X POST http://localhost:8000/api/slots/1/hold
```

**Ожидаемый ответ (400 Bad Request):**
```json
{
  "error": "Invalid or missing Idempotency-Key header",
  "details": {
    "idempotency_key": [
      "The idempotency key field is required."
    ]
  }
}
```

---

### 6. Создать холд с невалидным UUID

```bash
curl -X POST http://localhost:8000/api/slots/1/hold -H "Idempotency-Key: invalid-key-123"
```

**Ожидаемый ответ (400 Bad Request):**
```json
{
  "error": "Invalid or missing Idempotency-Key header",
  "details": {
    "idempotency_key": [
      "The idempotency key field must be a valid UUID."
    ]
  }
}
```

---

### 7. Создать холд для несуществующего слота

```bash
curl -X POST http://localhost:8000/api/slots/999/hold -H "Idempotency-Key: 550e8400-e29b-41d4-a716-446655440002"
```

**Ожидаемый ответ (404 Not Found):**
```json
{
  "error": "Slot not found"
}
```

---

## Подтверждение холдов

### 8. Подтвердить холд (успешно)

```bash
curl -X POST http://localhost:8000/api/holds/1/confirm
```

**Ожидаемый ответ (200 OK):**
```json
{
  "id": 1,
  "slot_id": 1,
  "idempotency_key": "550e8400-e29b-41d4-a716-446655440001",
  "status": "confirmed",
  "expires_at": "2025-10-30T01:05:00.000000Z",
  "created_at": "2025-10-30T01:00:00.000000Z",
  "updated_at": "2025-10-30T01:02:00.000000Z",
  "slot": {
    "id": 1,
    "name": "Склад А - окно 1",
    "capacity": 10,
    "remaining": 9
  }
}
```

**Обратите внимание:**
- `status` изменился на `confirmed`
- `remaining` уменьшился с 10 до 9

---

### 9. Повторное подтверждение (ошибка)

```bash
curl -X POST http://localhost:8000/api/holds/1/confirm
```

**Ожидаемый ответ (409 Conflict):**
```json
{
  "error": "Hold already confirmed"
}
```

---

### 10. Подтвердить несуществующий холд

```bash
curl -X POST http://localhost:8000/api/holds/999/confirm
```

**Ожидаемый ответ (404 Not Found):**
```json
{
  "error": "Hold not found"
}
```

---

## Отмена холдов

### 11. Отменить холд (успешно)

```bash
curl -X DELETE http://localhost:8000/api/holds/1
```

**Ожидаемый ответ (200 OK):**
```json
{
  "id": 1,
  "slot_id": 1,
  "idempotency_key": "550e8400-e29b-41d4-a716-446655440001",
  "status": "cancelled",
  "expires_at": "2025-10-30T01:05:00.000000Z",
  "created_at": "2025-10-30T01:00:00.000000Z",
  "updated_at": "2025-10-30T01:03:00.000000Z",
  "slot": {
    "id": 1,
    "name": "Склад А - окно 1",
    "capacity": 10,
    "remaining": 10
  }
}
```

**Обратите внимание:**
- `status` изменился на `cancelled`
- `remaining` вернулся к 10 (если холд был подтвержден)

---

### 12. Отменить несуществующий холд

```bash
curl -X DELETE http://localhost:8000/api/holds/999
```

**Ожидаемый ответ (404 Not Found):**
```json
{
  "error": "Hold not found"
}
```

---

## Тест оверсела (защита от race conditions)

### 13. Создать несколько холдов для слота с capacity=1

```bash
# Создать слот с capacity=1 и remaining=1 (через БД, сидер или curl запросы уменьшить remaining у существующего слота)

# Создать первый холд
curl -X POST http://localhost:8000/api/slots/3/hold -H "Idempotency-Key: 550e8400-e29b-41d4-a716-446655440010"

# Подтвердить первый холд
curl -X POST http://localhost:8000/api/holds/10/confirm

# Попытаться создать второй холд (должно быть ОК, т.к. холд не занимает место)
curl -X POST http://localhost:8000/api/slots/3/hold -H "Idempotency-Key: 550e8400-e29b-41d4-a716-446655440011"

# Попытаться подтвердить второй холд (должна быть ошибка)
curl -X POST http://localhost:8000/api/holds/11/confirm
```

**Ожидаемый ответ для последнего запроса (409 Conflict):**
```json
{
  "error": "No available capacity"
}
```

---
## Тест производительности кеша

```bash
# Очистить кеш Redis
docker-compose exec redis redis-cli FLUSHALL

# Первый запрос (из БД, медленнее)
echo "=== Первый запрос (из БД) ==="
time curl http://localhost:8000/api/slots/availability

# Второй запрос (из кеша, быстрее)
echo "=== Второй запрос (из кеша) ==="
time curl http://localhost:8000/api/slots/availability

# Подождать 11 секунд (TTL = 10 секунд)
echo "=== Ожидание истечения кеша (11 секунд) ==="
sleep 11

# Третий запрос (кеш истек, снова из БД)
echo "=== Третий запрос (кеш истек) ==="
time curl http://localhost:8000/api/slots/availability
```

---

## 🔍 Проверка заголовков

```bash
# Получить все заголовки ответа
curl -v http://localhost:8000/api/slots/availability 2>&1 | grep -E "(X-Cache-Hit|X-Data-Source)"

# Или с помощью -I (только заголовки)
curl -I http://localhost:8000/api/slots/availability
```

**Ожидаемые заголовки:**
```
X-Cache-Hit: true
X-Data-Source: cache
```

---

## Параллельные запросы (race condition test)

```bash
# Создать 5 параллельных запросов на подтверждение одного слота
for i in {1..5}; do
  curl -X POST http://localhost:8000/api/slots/1/hold \
    -H "Idempotency-Key: $(uuidgen)" \
    -H "Content-Type: application/json" &
done
wait

# Только один должен успешно подтвердиться, остальные получат 409
```


## PowerShell эквиваленты

Для Windows PowerShell используйте `Invoke-RestMethod`:

```powershell
# Получить слоты
Invoke-RestMethod -Uri "http://localhost:8000/api/slots/availability"

# Создать холд
$headers = @{"Idempotency-Key"="550e8400-e29b-41d4-a716-446655440001"}
Invoke-RestMethod -Uri "http://localhost:8000/api/slots/1/hold" -Method Post -Headers $headers

# Подтвердить холд
Invoke-RestMethod -Uri "http://localhost:8000/api/holds/1/confirm" -Method Post

# Отменить холд
Invoke-RestMethod -Uri "http://localhost:8000/api/holds/1" -Method Delete
```
