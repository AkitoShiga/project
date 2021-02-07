#イメージのベースを指定
FROM php_v1.5

#composerのインストール
COPY --from=composer:2.0.8 /usr/bin/composer /usr/bin/composer

#npmのインストール
COPY --from=node:14.15 /usr/local/bin /usr/local/bin
COPY --from=node:14.15 /usr/local/lib /usr/local/lib

#パッケージ管理ツールapt-getの更新と必要パッケージのインストール
RUN apt-get update \
&& apt-get install -y \
git \
zip \
unzip \
&& docker-php-ext-install pdo_mysql bcmath

WORKDIR /var/www/html

#コンテナ内に入った時のディレクトリを指定
WORKDIR /var/www/html
