# Is_Literal Examples

- SQL Basic  - [Experiment](https://3v4l.org/SaXWB/rfc#vrfc.literals), [Source](./sql-basic.php)
- SQL ORM    - [Experiment](https://3v4l.org/uOcNZ/rfc#vrfc.literals), [Source](./sql-orm.php)
- HTML Basic - [Experiment](https://3v4l.org/cTrIu/rfc#vrfc.literals), [Source](./html-basic.php)

---

## To run Static Analysis

To install:

```cli
composer require --dev phpstan/phpstan
composer require --dev vimeo/psalm
./vendor/bin/psalm --init ./ 1
```

To check:

```
./vendor/bin/phpstan analyse -l 9 ./*-sa.php
./vendor/bin/psalm ./*-sa.php
```

Note, the ORM example does not work particularly well, due to the recursive nature of the `$conditions` array.
