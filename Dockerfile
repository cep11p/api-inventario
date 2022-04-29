FROM yiisoftware/yii2-php:7.3-apache

COPY --chown=www-data:www-data . /app

RUN mkdir -p /app/runtime/sessions /app/web/assets /app/runtime/cache /app/_files /queue
RUN touch /app/runtime/logs/app.log
RUN chown www-data:www-data /app/runtime/sessions /app/runtime/cache /app/web/assets /app/_files /app/runtime/logs/app.log /queue

WORKDIR /app


