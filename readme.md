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

But, in short, abstractions like [Doctrine could protect itself against common mistakes](https://www.doctrine-project.org/projects/doctrine-orm/en/2.7/reference/security.html) like this:

```php
$query = $em->createQuery('SELECT u FROM User u WHERE u.id = ' . $_GET['id']);
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

This uses a similar definition of [SafeConst](https://wiki.php.net/rfc/sql_injection_protection#safeconst) from Matt Tait's RFC, but it doesn't need to accept Integer or FloatingPoint variables as safe (unless it makes the implementation easier), nor should this proposal effect any existing functions.

Thanks to [NikiC](https://chat.stackoverflow.com/transcript/message/51565346#51565346), it looks like we can reuse the GC_PROTECTED flag for strings.

As an aside, [Xinchen Hui](https://news-web.php.net/php.internals/87396) found the Taint extension was complex in PHP5, but "with PHP7's new zend_string, and string flags, the implementation will become easier". Also, [MarkR](https://chat.stackoverflow.com/transcript/message/48927813#48927813) suggested that it might be possible to use the fact that "interned strings in PHP have a flag", which is there because these "can't be freed".

Unlike the Taint extension, there must **not** be an equivalent `untaint()` function, or support any kind of escaping.

## Previous Work

There is the [Taint extension](https://github.com/laruence/taint) by Xinchen Hui, but this approach explicitly allows escaping, which doesn't address all issues.

Google currently uses a [similar approach in Go](https://github.com/craigfrancis/php-is-literal-rfc/blob/main/justification.md#go-implementation) with the use of "compile time constants"; and there are [discussions about adding it to JavaScript](https://github.com/craigfrancis/php-is-literal-rfc/blob/main/justification.md#javascript-implementation).

It might be possible to use static analysis, for example [psalm](https://psalm.dev/) (thanks [Tyson Andre](https://news-web.php.net/php.internals/109192)). But I can't find any which do these checks by default, they are likely to miss things that happen at runtime, and we can't expect all programmers to use static analysis (especially those who have just stated, who need this more than developers who know the concepts and just make the odd mistake).

And there is the [Automatic SQL Injection Protection](https://wiki.php.net/rfc/sql_injection_protection) RFC by Matt Tait, where it was noted:

* "unfiltered input can affect way more than only SQL" ([Pierre Joye](https://news-web.php.net/php.internals/87355));
* this amount of work isn't ideal for "just for one use case" ([Julien Pauli](https://news-web.php.net/php.internals/87647));
* It would have effected every SQL function, such as `mysqli_query()`, `$pdo->query()`, `odbc_exec()`, etc (concerns raised by [Lester Caine](https://news-web.php.net/php.internals/87436) and [Anthony Ferrara](https://news-web.php.net/php.internals/87650));
* Each of those functions would need a bypass for cases where unsafe SQL was intentionally being used (e.g. phpMyAdmin taking SQL from POST data) because some applications intentionally "pass raw, user submitted, SQL" (Ronald Chmara [1](https://news-web.php.net/php.internals/87406)/[2](https://news-web.php.net/php.internals/87446)).

I also agree that "SQL injection is almost a solved problem [by using] prepared statements" ([Scott Arciszewski](https://news-web.php.net/php.internals/87400)), but we still need something to identify mistakes.

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

- Should this be named something else? ([Jakob Givoni](https://news-web.php.net/php.internals/109197) suggested `is_from_literal`).
- Would this cause performance issues?
- Can `array_fill()`+`implode()` pass though the "is_literal" flag for the "WHERE IN" case?
- Systems/Frameworks that define certain variables (e.g. table name prefixes) without the use of a literal (e.g. ini/json/yaml files), they might need to make some changes to use this check, as originally noted by [Dennis Birkholz](https://news-web.php.net/php.internals/87667).

## Unaffected PHP Functionality

Not sure

## Future Scope

As noted by [MarkR](https://chat.stackoverflow.com/transcript/message/51573226#51573226), the biggest benefit will come when it can be used by PDO and similar functions (`mysqli_query`, `preg_match`, `exec`, etc). But the basic idea can be used immediately by frameworks and general abstraction libraries, and they can give feedback for phase 2.

This check could be used to throw an exception, or generate an error/warning/notice, providing a way for PHP to teach new programmers, and/or completely block unsafe values in SQL, HTML, CLI, etc.

PHP could have a mode where output (e.g. `echo '<html>'`) is blocked, and this can be bypassed (maybe via `ini_set`) when the HTML Templating Engine has created the correctly encoded output.

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
