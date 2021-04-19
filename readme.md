# PHP RFC: Is Literal Check

* Version: 0.4
* Date: 2020-03-21
* Updated: 2021-02-19
* Author: Craig Francis, craig#at#craigfrancis.co.uk
* Status: Draft
* First Published at: https://wiki.php.net/rfc/is_literal
* GitHub Repo: https://github.com/craigfrancis/php-is-literal-rfc

## Introduction

This RFC proposes a new function, `is_literal(string $string)`, to help enforce a separation of hard-coded logic from user-supplied data. This addresses some of the same use cases as "taint flags", but is both simpler and stricter: it does not address how user data is transmitted or escaped, only whether it has been passed to a particular library function separately from the fixed data.

The clearest example is a database library which supports parametrised queries at the driver level. The correct usage would be something like ''$db->query("Select * From users Where id = ?", [$_GET['id']]);'' but the user could also write ''$db->query("Select * From users Where id = " . $_GET['id']);'' By rejecting the SQL if it was not written as a literal, the library can provide extra protection against this incorrect use.


## Examples

The [[https://www.doctrine-project.org/projects/doctrine-orm/en/current/reference/query-builder.html#high-level-api-methods|Doctrine Query Builder]] allows custom Where clauses to be provided as strings. This is intended for use with literals and placeholders, but does not protect against this simple mistake:

```php
// INSECURE
$qb->select('u')
   ->from('User', 'u')
   ->where('u.id = ' . $_GET['id'])
```

The definition of the ''where'' method could check with ''is_literal'' and throw an exception advising the programmer to replace it with a safer use of placeholders:

```php
$qb->select('u')
   ->from('User', 'u')
   ->where('u.id = :identifier')
   ->setParameter('identifier', $_GET['id']);
```

Similarly, Twig allows [[https://twig.symfony.com/doc/2.x/recipes.html#loading-a-template-from-a-string|loading a template from a string]], which could allow accidentally skipping the default escaping functionality:

```php
// INSECURE
echo $twig->createTemplate('<p>Hi ' . $_GET['name'] . '</p>')->render();
```

If ''createTemplate'' checked with ''is_literal'', the programmer could be advised to write this instead:

```php
echo $twig->createTemplate('<p>Hi {{ name }}</p>')->render(['name' => $_GET['name']]);
```

## Proposal

A literal is defined as any value which is entirely under the control of the programmer. The value may be passed between functions, as long as it is not modified in any way other than string concatenation.

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
is_literal($a); // true, the one exception that involves functions. [TODO: this exception is controversial]
```

Note that there is no way to manually mark a string as "safe" (i.e. no equivalent to ''untaint()''); as soon as the value has been manipulated in any way, it is no longer marked as a literal.


## Implementation Notes

(Most of what's in this section probably doesn't need to be in the final RFC.)

Ideally string concatenation would be allowed, but [Danack](https://github.com/Danack/RfcLiteralString/issues/5) suggested this might raise performance concerns, and an array implode like function could be used instead (or a query builder).

Thanks to [NikiC](https://chat.stackoverflow.com/transcript/message/51565346#51565346), it looks like we can reuse the GC_PROTECTED flag for strings.

As an aside, [Xinchen Hui](https://news-web.php.net/php.internals/87396) found the Taint extension was complex in PHP5, but "with PHP7's new zend_string, and string flags, the implementation will become easier". Also, [MarkR](https://chat.stackoverflow.com/transcript/message/48927813#48927813) suggested that it might be possible to use the fact that "interned strings in PHP have a flag", which is there because these "can't be freed".

## Comparison to Taint Tracking

Some languages implement a "taint flag" which tracks whether values are considered "safe". There is a [[https://github.com/laruence/taint|Taint extension for PHP]] by Xinchen Hui, and [[rfc/taint|a previous RFC proposing it be added to the language]].

These solutions rely on the assumption that the output of an escaping function is safe for a particular context. This sounds reasonable in theory, but the operation of escaping functions, and the context for which their output is safe, are very hard to define. This leads to a feature that is both complex and unreliable.

The current proposal avoids this complexity by addressing a different part of the problem: separating inputs supplied by the programmer from inputs supplied by the user.

## Previous Work

Google currently uses a [similar approach in Go](https://github.com/craigfrancis/php-is-literal-rfc/blob/main/justification.md#go-implementation) which uses "compile time constants", [Perl has a Taint Mode](https://github.com/craigfrancis/php-is-literal-rfc/blob/main/justification.md#perl-implementation) (but uses regular expressions to un-taint data), and there are discussions about [adding it to JavaScript](https://github.com/craigfrancis/php-is-literal-rfc/blob/main/justification.md#javascript-implementation) to support Trusted Types.

As noted be [Tyson Andre](https://news-web.php.net/php.internals/109192), it might be possible to use static analysis, for example [psalm](https://psalm.dev/). But I can't find any which do these checks by default, [can be incomplete](https://github.com/vimeo/psalm/commit/2122e4a1756dac68a83ec3f5abfbc60331630781), they are likely to miss things (especially at runtime), and we can't expect all programmers to use static analysis (especially those who are new to programming, who need this more than developers who know the concepts and just make the odd mistake).

And there is the [Automatic SQL Injection Protection](https://wiki.php.net/rfc/sql_injection_protection) RFC by Matt Tait, where this RFC uses a similar concept of the [SafeConst](https://wiki.php.net/rfc/sql_injection_protection#safeconst). When Matt's RFC was being discussed, it was noted:

* "unfiltered input can affect way more than only SQL" ([Pierre Joye](https://news-web.php.net/php.internals/87355));
* this amount of work isn't ideal for "just for one use case" ([Julien Pauli](https://news-web.php.net/php.internals/87647));
* It would have effected every SQL function, such as `mysqli_query()`, `$pdo->query()`, `odbc_exec()`, etc (concerns raised by [Lester Caine](https://news-web.php.net/php.internals/87436) and [Anthony Ferrara](https://news-web.php.net/php.internals/87650));
* Each of those functions would need a bypass for cases where unsafe SQL was intentionally being used (e.g. phpMyAdmin taking SQL from POST data) because some applications intentionally "pass raw, user submitted, SQL" (Ronald Chmara [1](https://news-web.php.net/php.internals/87406)/[2](https://news-web.php.net/php.internals/87446)).

I also agree that "SQL injection is almost a solved problem [by using] prepared statements" ([Scott Arciszewski](https://news-web.php.net/php.internals/87400)), but we still `is_literal()` to identify mistakes.

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

For example, a project could require the second argument for `pg_query()` only accept literals or their `query_builder` object (which provides a `__toString` method); and that any output (print, echo, readfile, etc) must use the `html_output` object that's returned by their trusted HTML Templating system (using `ob_start()` might be useful here).

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
