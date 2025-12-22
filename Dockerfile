FROM php:8.2-cli

WORKDIR /app

COPY . .

RUN mkdir -p cache && chmod -R 777 cache

EXPOSE 10000

CMD ["php", "-S", "0.0.0.0:10000", "-t", "public"]
