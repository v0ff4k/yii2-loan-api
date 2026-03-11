# Loan API Project

Проект API для подачи и обработки заявок на займ, реализованный на фреймворке Yii2 с использованием
PostgreSQL, Nginx и Docker Compose.

## Требования к окружению
- Docker
- Docker Compose
- PHP 8.2+ (требуется для запуска в контейнере)

## Запуск проекта

1. Клонируйте репозиторий или распакуйте архив.

2. Скопируйте `.env.example` в `.env` и при необходимости измените параметры:
   ```bash
   cp .env.example .env
   ```

3. В корне проекта выполните команду для сборки и запуска контейнеров:
   ```bash
   docker-compose up -d --build
   ```

4. Запуск миграции:
   ```bash
   docker-compose exec php php /app/src/yii migrate --interactive=0
   ```

5. Запуск тестов:
   ```bash
   docker-compose exec php vendor/bin/phpunit --testdox --colors=always
   ```

## API Endpoints

### POST /requests
Подача новой заявки на займ.

**Request:**
```json
{
  "user_id": 1,
  "amount": 3000,
  "term": 30
}
```

**Параметры:**
| Параметр | Тип | Описание |
|----------|-----|----------|
| user_id | integer | Идентификатор пользователя |
| amount | integer | Сумма займа |
| term | integer | Срок займа в днях |

**Ответ успешный (HTTP 201):**
```json
{
  "result": true,
  "id": 42
}
```

**Ответ неуспешный (HTTP 400):**
```json
{
  "result": false
}
```

**Возможные ошибки:**
- `400 Bad Request` — не пройдена валидация или у пользователя уже есть одобренная заявка

---

### GET /processor?delay=5
Запуск обработки заявок на займ.

**Параметры:**
| Параметр | Тип | Описание |
|----------|-----|----------|
| delay | integer | Задержка в секундах для эмуляции принятия решения |

**Описание:**
- Обрабатываются все заявки со статусом `pending`
- Решение принимается случайно: 10% вероятность одобрения
- У одного пользователя не может быть более одной одобренной заявки
- Используется `sleep()` для эмуляции задержки

**Ответ успешный (HTTP 200):**
```json
{
  "result": true
}
```

---

## Покрытие тестами

- Валидация модели (обязательные поля, типы данных)
- Создание успешной заявки
- Отклонение при наличии одобренной заявки
- Обработка процессором (pending → approved/declined)
- Игнорирование уже обработанных заявок
- HTTP коды ответов (200, 201, 400)
- Тесты сервисного слоя (LoanService)
- Тесты граничных значений

## Ручное тестирование

Подача заявки:
```bash
curl -X POST http://localhost/requests \
  -H "Content-Type: application/json" \
  -d '{"user_id":1,"amount":3000,"term":30}'
```

Обработка заявок:
```bash
curl http://localhost/processor?delay=1
```

## Структура проекта

```
yii2-loan-api/
├── docker/
│   ├── php/
│   │   ├── Dockerfile
│   │   └── php.ini
│   └── nginx/
│       └── default.conf
├── src/
│   ├── config/
│   │   ├── db.php
│   │   ├── web.php
│   │   └── params.php
│   ├── console/
│   │   └── migrations/
│   │       └── m231027_100000_create_loan_requests_table.php
│   ├── controllers/
│   │   └── LoanController.php
│   ├── models/
│   │   └── LoanRequest.php
│   ├── services/
│   │   └── LoanService.php
│   └── web/
│       └── index.php
├── tests/
│   ├── unit/
│   │   ├── models/
│   │   │   └── LoanRequestTest.php
│   │   ├── controllers/
│   │   │   └── LoanControllerTest.php
│   │   └── services/
│   │       └── LoanServiceTest.php
│   └── bootstrap.php
├── .env.example
├── .gitignore
├── composer.json
├── docker-compose.yml
├── phpunit.xml.dist
└── README.md
```

## Debug

### Полная пересборка
```bash
docker-compose down -v
docker-compose build --no-cache
docker-compose up -d
```

### Просмотр логов
```bash
docker-compose logs -f php
```

### Тесты
```bash
docker-compose exec php vendor/bin/phpunit --testdox --colors=always
```

## Затраченное время
1. Docker и окружение: 3 часа
2. Код и рефакторинг: 2 часа
3. Тесты: 1 час
4. Рефак 1ч.

---

## TODO

### В процессе / будущие улучшения

- **Добавить rate limiting** — защита эндпоинта `/processor` от частых вызовов
- **Добавить функциональные (HTTP) тесты** — тестирование через реальные HTTP-запросы
- **Добавить валидацию данных** — ограничения на минимальную/максимальную сумму и срок займа
