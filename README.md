
## Запуск

```shell
docker-compose up -d
docker-compose exec php composer install
```

### Поменять если нужно
docker-compose.yml
`nginx:` -> `ports:` -> `- "8080:80"`

## Пользоваться
Тестовый пользователь: `admin@example.com` / `admin123`

### Пересоздать тестовую базу - для тестов
```shell
docker-compose exec php bash -c "
php bin/console doctrine:database:drop --env=test --force --if-exists &&
php bin/console doctrine:database:create --env=test &&
php bin/console doctrine:schema:create --env=test &&
php bin/console doctrine:fixtures:load --env=test --no-interaction
"
```

### Тест до ошибки
```shell
docker-compose exec php php bin/phpunit --stop-on-failure
```


## Технологии
- symfony 5.4
- php 8.1

### Пакеты php-symfony
- symfony/security-bundle 
- symfony/orm-pack 
- symfony/twig-bundle 
- symfony/validator
- symfony/form
- [dev] symfony/maker-bundle
- [dev] symfony/test-pack
- [dev] doctrine/doctrine-fixtures-bundle
