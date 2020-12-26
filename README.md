#Simple ps5 checker

POC about checking if PS5 is available on few stores and send notification on Telegram if PS5 is available.

###usage
add cron to crontab
```
0/10 * * * * bin/console app:ps5
```

edit TELEGRAM_DSN in .env