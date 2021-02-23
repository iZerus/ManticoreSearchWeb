# Настройка поисковика ManticoreWebSearch
Установка Manticore на Ubuntu в этой [статье](https://gist.github.com/iZerus/8f82bb1cc2b14a8b38a1b22e1f130386).

Необходимо **проверить** работу `.htaccess`! Инфа есть на этой [странице](https://askubuntu.com/questions/422027/mod-rewrite-is-enabled-but-not-working).

Пример в index.html

Для скриптов переиндексации выполняем следующее:

- Создаем файл `config.ini` и вводим там индекс и токен `master59ru=12345`
- Файл `indexer.sh` должен быть **испольняемым**!
- Вводим `sudo visudo` и редактируем права пользователя apache2 на скрипт `indexer.sh`
```
www-data ALL=NOPASSWD: /var/www/html/.../indexer.sh
```

Чтобы выполнить индексацию обращаемся к скрипту:

.../indexer.php?index=`имя_индекса`&token=`токен`