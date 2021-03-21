# PHP RFC: Is Literal Check

* Version: 0.3
* Date: 2020-03-21
* Updated: 2021-02-19
* Author: Craig Francis, craig#at#craigfrancis.co.uk
* Status: Draft
* First Published at: https://wiki.php.net/rfc/is_literal
* GitHub Repo: https://github.com/craigfrancis/php-is-literal-rfc

## Introduction

Add an `is_literal()` function, so developers/frameworks can check if a given variable is **safe**.

As in, at runtime, being able to check if a variable has been created by literals, defined within a PHP script, by a trusted developer.

This simple check can be used to warn or completely block SQL Injection, Command Line Injection, and many cases of HTML Injection (aka XSS).

See the [justification for why this is important](https://github.com/craigfrancis/php-is-literal-rfc/blob/main/justification.md).

In short, abstractions like Doctrine could protect against [common mistakes](https://www.doctrine-project.org/projects/doctrine-orm/en/2.7/reference/security.html), like this [Query Builder](https://www.doctrine-project.org/projects/doctrine-orm/en/2.7/reference/query-builder.html#high-level-api-methods) example:

```php
$users = $queryBuilder
  ->select('u')
  ->from('User', 'u')
  ->where('u.id = ' . $_GET['id'])
  ->getQuery()
  ->getResult();
```

Or this Twig [HTML Template](https://twig.symfony.com/doc/2.x/recipes.html#loading-a-template-from-a-string):

```php
echo $twig->createTemplate('<p>Hi ' . $_GET['name'] . '</p>')->render();
```

## Proposal

Literals are safe values, defined within the PHP script, for example:

```php
is_literal('Example'); // true

$a = 'Example';
is_literal($a); // true

is_literal(4); // true
is_literal(0.3); // true
is_literal('a' . 'b'); // true, compiler can concatenate

$a = 'A';
$b = $a . ' B ' . 3;
is_literal($b); // true, ideally (more details below)

is_literal($_GET['id']); // false

is_literal(rand(0, 10)); // false

is_literal(sprintf('LIMIT %d', 3)); // false

$c = count($ids);
$a = 'WHERE id IN (' . implode(',', array_fill(0, $c, '?')) . ')';
is_literal($a); // true, the one exception that involves functions.
```

Ideally string concatenation would be allowed, but [Danack](https://github.com/Danack/RfcLiteralString/issues/5) suggested this might raise performance concerns, and an array implode like function could be used instead (or a query builder).

Thanks to [NikiC](https://chat.stackoverflow.com/transcript/message/51565346#51565346), it looks like we can reuse the GC_PROTECTED flag for strings.

As an aside, [Xinchen Hui](https://news-web.php.net/php.internals/87396) found the Taint extension was complex in PHP5, but "with PHP7's new zend_string, and string flags, the implementation will become easier". Also, [MarkR](https://chat.stackoverflow.com/transcript/message/48927813#48927813) suggested that it might be possible to use the fact that "interned strings in PHP have a flag", which is there because these "can't be freed".

Unlike the Taint extension, there must **not** be an equivalent `untaint()` function, or support any kind of escaping.

## Previous Work

There is the [Taint extension](https://github.com/laruence/taint) by Xinchen Hui, but in contrast to this, there must **not** be an equivalent `untaint()` function, or support any kind of escaping (see the [justification page](https://github.com/craigfrancis/php-is-literal-rfc/blob/main/justification.md)).

Google currently uses a [similar approach in Go](https://github.com/craigfrancis/php-is-literal-rfc/blob/main/justification.md#go-implementation) which uses "compile time constants", [Perl has a Taint Mode](https://github.com/craigfrancis/php-is-literal-rfc/blob/main/justification.md#perl-implementation) (but uses regular expressions to un-taint data), and there are discussions about [adding it to JavaScript](https://github.com/craigfrancis/php-is-literal-rfc/blob/main/justification.md#javascript-implementation) to support Trusted Types.

As noted be [Tyson Andre](https://news-web.php.net/php.internals/109192), it might be possible to use static analysis, for example [psalm](https://psalm.dev/). But I can't find any which do these checks by default, [can be incomplete](https://github.com/vimeo/psalm/commit/2122e4a1756dac68a83ec3f5abfbc60331630781), they are likely to miss things (especially at runtime), and we can't expect all programmers to use static analysis (especially those who have just stated, who need this more than developers who know the concepts and just make the odd mistake).

And there is the [Automatic SQL Injection Protection](https://wiki.php.net/rfc/sql_injection_protection) RFC by Matt Tait, where this RFC uses a similar concept of the [SafeConst](https://wiki.php.net/rfc/sql_injection_protection#safeconst). When Matt's RFC was being discussed, it was noted:

* "unfiltered input can affect way more than only SQL" ([Pierre Joye](https://news-web.php.net/php.internals/87355));
* this amount of work isn't ideal for "just for one use case" ([Julien Pauli](https://news-web.php.net/php.internals/87647));
* It would have effected every SQL function, such as `mysqli_query()`, `$pdo->query()`, `odbc_exec()`, etc (concerns raised by [Lester Caine](https://news-web.php.net/php.internals/87436) and [Anthony Ferrara](https://news-web.php.net/php.internals/87650));
* Each of those functions would need a bypass for cases where unsafe SQL was intentionally being used (e.g. phpMyAdmin taking SQL from POST data) because some applications intentionally "pass raw, user submitted, SQL" (Ronald Chmara [1](https://news-web.php.net/php.internals/87406)/[2](https://news-web.php.net/php.internals/87446)).

I also agree that "SQL injection is almost a solved problem [by using] prepared statements" ([Scott Arciszewski](https://news-web.php.net/php.internals/87400)), but we still need the `is_literal()` check to identify mistakes.

## Backward Incompatible Changes

None

## Proposed PHP Version(s)

PHP 8.1?

## RFC Impact

### To SAPIs

Not sure

### To Existing Extensions

Not sure

### To Opcache

Not sure

## Open Issues

On [GitHub](https://github.com/craigfrancis/php-is-literal-rfc/issues):

- Name it something else? [Jakob Givoni](https://news-web.php.net/php.internals/109197) suggested `is_from_literal()`; or maybe `is_safe()`.
- Would this cause performance issues?
- Can `array_fill()`+`implode()` pass though the "is_literal" flag for the "WHERE IN" case?
- Systems/Frameworks that define certain variables (e.g. table name prefixes) without the use of a literal (e.g. ini/json/yaml files), they might need to make some changes to use this check, as originally noted by [Dennis Birkholz](https://news-web.php.net/php.internals/87667).

## Unaffected PHP Functionality

Not sure

## Future Scope

As noted by [MarkR](https://chat.stackoverflow.com/transcript/message/51573226#51573226), the biggest benefit will come when it can be used by PDO and similar functions (`mysqli_query`, `preg_match`, `exec`, etc). But the basic idea can be used immediately by frameworks and general abstraction libraries, and they can give feedback for future work.

**Phase 2** could introduce a way for programmers to specify that certain function arguments only accept safe literals, and/or specific value-objects their project trusts (this idea comes from [Trusted Types](https://web.dev/trusted-types/) in JavaScript).

For example, a project could require the second argument for `pg_query()` to only accept literals or their `query_builder` object (which provides a `__toString` method); and that any output (print, echo, readfile, etc) must use the `html_output` object that's returned by their trusted HTML Templating system (using `ob_start()` might be useful here).

**Phase 3** could set a default of 'only literals' for all of the relevant PHP function arguments, so developers are given a warning, and later prevented (via an exception), when they provide an unsafe value to those functions (they could still specify that unsafe values are allowed, e.g. phpMyAdmin).

And, for a bit of silliness (Spaß ist verboten), there could be a `is_figurative()` function, which MarkR seems to [really](https://chat.stackoverflow.com/transcript/message/48927770#48927770), [want](https://chat.stackoverflow.com/transcript/message/51573091#51573091) :-)

## Proposed Voting Choices

N/A

## Patches and Tests

N/A

## Implementation

[Danack](https://github.com/Danack/) has [started an implementation](https://github.com/php/php-src/compare/master...Danack:is_literal_attempt_two).

## References

N/A

## Rejected Features

N/A
