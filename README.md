# Loan API Project

Проект API для подачи и обработки заявок на займ, реализованный на фреймворке Yii2 с использованием 
PostgreSQL, Nginx и Docker Compose.

## Требования к окружению
- Docker
- Docker Compose
- PHP 8.0+

## Запуск проекта

1. Клонируйте репозиторий или распакуйте архив. 

2. Смените дефолтный ВЕБ порт **8380** на **80** в _docker-compose.yml_  тамже доступ к БД **5430** извне(если нужен)

3. В корне проекта выполните команду для сборки и запуска контейнеров: `docker-compose up -d --build`

4. Установка зависимостей(идет в билде): `composer install` 

5. Запуск миграции: `docker-compose exec php php /app/src/yii migrate --interactive=0`

6. Запуск тестов: `docker-compose exec php vendor/bin/phpunit --testdox --colors=always` (в цвете)


## Покрытие тестами

    - Валидация модели (обязательные поля, типы данных)
    - Создание успешной заявки
    - Отклонение при наличии одобренной заявки
    - Обработка процессором (pending → approved/declined)
    - Игнорирование уже обработанных заявок
    - HTTP коды ответов (200, 201, 400)

Ручной тест: Если порт(8380) не менялся:
Добавление `curl -X POST http://localhost:8380/requests -H "Content-Type: application/json" -d '{"user_id":1,"amount":3000,"term":30}`
ОбработкаЖ `http://localhost:8380/processor?delay=1`
Заглушка с **Empty !)** текстом по адресу `http://localhost:8380` - для проверки запуска.

### template structure
    
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
    │   └── web/
    │       └── index.php
    ├── tests/
    │   ├── unit/
    │   │   ├── models/
    │   │   │   └── LoanRequestTest.php
    │   │   └── controllers/
    │   │       └── LoanControllerTest.php
    │   ├── _output/
    │   └── bootstrap.php
    ├── vendor/   (создаётся после composer install)
    ├── composer.json
    ├── composer.lock   (создаётся после composer install)
    ├── docker-compose.yml
    ├── codeception.yml
    ├── phpunit.xml.dist
    ├── .gitignore
    ├── .dockerignore
    ├── README.md
    └── TODO.md

## Debug

### Полная пересборка
    docker-compose down -v
    docker-compose build --no-cache
    docker-compose up -d

### Смотрим логи ( Composer + миграции выполнятся при старте)
    docker-compose logs -f php

### Tests

run: `docker-compose exec php vendor/bin/phpunit --testdox --colors=always`

Ручной тест: `curl -X POST http://localhost:8380/requests -H "Content-Type: application/json" -d '{"user_id":1,"amount":3000,"term":30}'`