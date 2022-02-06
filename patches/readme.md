
Apply the patch to the PHP source:

	cd src/php-8.1.2

    patch --dry-run -p1 < ../../patches/8.1.2-main.diff

    patch -p1 < ../../patches/8.1.2-main.diff
    patch -p1 < ../../patches/8.1.2-tests.diff

    php Zend/zend_vm_gen.php

To create a new patch:

	diff -ur ./php-8.1.2-orig/ ./php-8.1.2/ > ../patches/8.1.2-main.diff

	Edit the diff, so it's folders "a/" and "b/", and no timestamp.

Then compile...

```
brew install bison re2c libiconv icu4c mhash;

export PATH="/usr/local/opt/bison/bin:$PATH" && \
export PKG_CONFIG_PATH="/usr/local/opt/openssl/lib/pkgconfig:/usr/local/opt/icu4c/lib/pkgconfig" && \
./buildconf && \
./configure \
    --enable-option-checking=fatal \
    --enable-opcache \
    --enable-fpm \
    --enable-cgi \
    --enable-bcmath \
    --enable-calendar \
    --enable-dba \
    --enable-exif \
    --enable-ftp \
    --enable-gd \
    --enable-mbregex \
    --enable-mbstring \
    --enable-mysqlnd \
    --enable-pcntl \
    --enable-phpdbg \
    --enable-phpdbg-readline \
    --enable-shmop \
    --enable-soap \
    --enable-sockets \
    --enable-sysvmsg \
    --enable-sysvsem \
    --enable-sysvshm \
    --enable-dtrace \
    --enable-intl \
    --with-curl \
    --with-external-gd \
    --with-external-pcre \
    --with-ffi \
    --with-fpm-user=_www \
    --with-fpm-group=_www \
    --with-iconv=/usr/local/opt/libiconv \
    --with-layout=GNU \
    --with-libxml \
    --with-libedit \
    --with-mhash=/usr/local/opt/mhash \
    --with-mysql-sock=/tmp/mysql.sock \
    --with-mysqli=mysqlnd \
    --with-openssl \
    --with-password-argon2=/usr/local/opt/argon2 \
    --with-pdo-dblib=/usr/local/opt/freetds \
    --with-pdo-mysql=mysqlnd \
    --with-pdo-odbc=unixODBC,/usr/local/opt/unixodbc \
    --with-pdo-pgsql=/usr/local/opt/libpq \
    --with-pdo-sqlite=/usr \
    --with-pgsql=/usr/local/opt/libpq \
    --with-pic \
    --with-pspell=/usr/local/opt/aspell \
    --with-sodium \
    --with-sqlite3 \
    --with-unixODBC \
    --with-xsl \
    --with-zip \
    --with-zlib && \
make && sed -i '' 's/9000/9001/g' ./sapi/fpm/www.conf && say "Done" || say "Error"; echo
```
