
Clone `github.com/php/php-src` into the './src/a/' folder.

Make a copy in './src/b/' and  './src/c/'.

Then apply the patches (with or without supporting string concat).

Then compile them:

```
cd ./src/a/;

export PATH="/usr/local/opt/bison/bin:$PATH"
export PKG_CONFIG_PATH="/usr/local/opt/openssl/lib/pkgconfig"
./buildconf
./configure \
    --enable-option-checking=fatal \
    --with-iconv=/usr/local/opt/libiconv/ \
    --with-openssl \
    --enable-mbstring \
    --enable-mysqlnd \
    --enable-opcache \
    --with-pdo-sqlite=/usr \
    --with-curl \
    --enable-fpm \
    --with-fpm-user=craig \
    --with-fpm-group=staff \
    --enable-cgi; \
    say "Done";
make && say "Done" || say "Error"; echo
```

---

Download Laravel and Symfony:

```
cd ./tests;

php ~/Downloads/composer.phar create-project laravel/laravel ./500-laravel 8.5.16 --no-interaction
php ~/Downloads/composer.phar dump-autoload --working-dir=./500-laravel --classmap-authoritative

sed -i".original" "s/'lottery' => \\[2, 100\\],/'lottery' => \\[0, 100\\],/g" ./500-laravel/config/session.php

php ~/Downloads/composer.phar create-project symfony/symfony-demo ./600-symfony dev-main --no-interaction
php ~/Downloads/composer.phar dump-autoload --working-dir=./600-symfony --classmap-authoritative
```

---

Running from a 3GB RAM Disk:

```
diskutil erasevolume HFS+ "MyRAMDisk" `hdiutil attach -nomount ram://6291456`

cp -a ./tests /Volumes/MyRAMDisk/tests;
cp -a ./src /Volumes/MyRAMDisk/src;
```

---

Find a way to switch off the CPU Turbo Boost.

Maybe via [Turbo Boost Switcher](https://www.rugarciap.com/).

---

Then run:

    ./run.sh

This creates 3 output files; the first is a copy of the HTML that Laravel/Symfony creates (to show that it's working), then the TXT file contains the results.

---

Alternatively,

The basic string concat tests can be run with:

    ./src/c/sapi/cli/php ./src/c/run-tests.php --show-diff ./tests/000.phpt

The performance test can be run with:

    for V in $(printf 'a b c %.0s' {1..11}); do echo -n $V; TEST=1 ./src/$V/sapi/cli/php ./tests/001.phpt; done

---

Notes

    https://github.com/kocsismate/php-version-benchmarks/

    --with-config-file-path=/Volumes/WebServer/Projects/php.isliteral/src/php.ini \

    #export REQUEST_URI="${SCRIPT_FILENAME}";

    make && say "Done" && echo && echo '---' && echo && ./sapi/cli/php ../../tests/000.phpt || say "Error"; echo; echo;

    ./sapi/cli/php ./ext/standard/tests/strings/literals/000-extra3.phpt
    ./sapi/cli/php run-tests.php --show-diff ./ext/standard/tests/strings/literals/000.phpt
