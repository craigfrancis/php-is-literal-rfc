# PHP RFC: Is Literal Check

* Version: 0.6
* Date: 2020-03-21
* Updated: 2021-04-30
* Author: Craig Francis, craig#at#craigfrancis.co.uk
* Status: Draft
* First Published at: https://wiki.php.net/rfc/is_literal
* GitHub Repo: https://github.com/craigfrancis/php-is-literal-rfc

## Introduction

A new function, `is_literal(string $string)`, to identify variables that have been created from a programmer defined string.

This takes the concept of "taint checking" and makes it simpler and stricter.

It does not allow a variable to be marked as untainted, and it does not allow escaping (important).

For example, take a database library that supports parametrised queries at the driver level, today a programmer could use either of these:

```php
$db->query('SELECT * FROM users WHERE id = ?', [$_GET['id']]);

$db->query('SELECT * FROM users WHERE id = ' . $_GET['id']); // INSECURE
```

If the library only accepted a literal SQL string (written by the programmer), and simply rejected the second example (not written as a literal), the library can provide an "inherently safe API".

This definition of an "inherently safe API" comes from Christoph Kern, who did a talk in 2016 about [Preventing Security Bugs through Software Design](https://www.youtube.com/watch?v=ccfEu-Jj0as) (also at [USENIX Security 2015](https://www.usenix.org/conference/usenixsecurity15/symposium-program/presentation/kern)), which covers how this concept is used at Google. The idea is that we "Don't Blame the Developer, Blame the API"; where we need to put the burden on libraries (written once, used by many) to ensure that it's impossible for the developer to make these mistakes.

By adding a way for libraries to check if the strings they receive came from the developer (from trusted PHP source code), it allows the library to check they are being used in a safe way.

## Why

The [OWASP Top 10](https://owasp.org/www-project-top-ten/) lists common vulnerabilities scored out of 3 for prevalence, exploitability, detectability, and impact.

The current list (2017) puts **Injection** vulnerabilities at the top (common prevalence, 2; easy for attackers to detect/exploit, 3; severe impact, 3); and **XSS** at 7 (widespread prevalence, 3; easy for attackers to detect/exploit, 3; moderate impact, 2).

And they have always been on the list - 2003 (A6/A4), 2004 (A6/A4), 2007 (A2/A1), 2010 (A1/A2), 2013 (A1/A3), 2017 (A1/A7).

These vulnerabilities are easy to make, and hard to identify - is_literal() directly addresses this.

## Examples

The [Doctrine Query Builder](https://www.doctrine-project.org/projects/doctrine-orm/en/current/reference/query-builder.html#high-level-api-methods) allows a custom WHERE clause to be provided as a string. This is intended for use with literals and placeholders, but it cannot protect against this simple mistake:

```php
// INSECURE
$qb->select('u')
   ->from('User', 'u')
   ->where('u.id = ' . $_GET['id'])
```

The definition of the `where()` method could check with `is_literal()` and throw an exception, advising the programmer to replace it with a safer use of placeholders:

```php
$qb->select('u')
   ->from('User', 'u')
   ->where('u.id = :identifier')
   ->setParameter('identifier', $_GET['id']);
```

Similarly, Twig allows [loading a template from a string](https://twig.symfony.com/doc/2.x/recipes.html#loading-a-template-from-a-string), which could allow accidentally skipping the default escaping functionality:

```php
// INSECURE
echo $twig->createTemplate('<p>Hi ' . $_GET['name'] . '</p>')->render();
```

If `createTemplate()` checked with `is_literal()`, the programmer could be advised to write this instead:

```php
echo $twig->createTemplate('<p>Hi {{ name }}</p>')->render(['name' => $_GET['name']]);
```

## Alternatives

These have been around for years, and haven't worked.

### Education

Developer training simply does not scale (people start programming every day), and learning about every single issue is difficult.

Keeping in mind that programmers will frequently do just enough to complete their task (busy), where they often download a library or copy/paste something (risky), don't really understand it (risky), modify it for their needs (risky), then move on.

We cannot keep saying they 'need to be careful', and rely on them to never make a mistake.

### Escaping

Escaping is *hard*, and *error prone*.

There is a long list of common [escaping mistakes](https://github.com/craigfrancis/php-is-literal-rfc/blob/main/justification.md#common-mistakes).

Developers should use parameterised queries (e.g. SQL), or a well tested library that knows how to escape values based on their context (e.g. HTML).

### Taint Checking

Some languages implement a "taint flag" which tracks whether values are considered "safe".

There is a [Taint extension for PHP](https://github.com/laruence/taint) by Xinchen Hui, and [a previous RFC proposing it be added to the language](https://wiki.php.net/rfc/taint) by Wietse Venema.

These solutions rely on the assumption that the output of an escaping function is safe for a particular context. This sounds reasonable in theory, but the operation of escaping functions, and the context for which their output is safe, are very hard to define. This leads to a feature that is both complex and unreliable.

This proposal avoids the complexity by addressing a different part of the problem: separating inputs supplied by the programmer, from inputs supplied by the user.

### Static Analysis

While I agree with [Tyson Andre](https://news-web.php.net/php.internals/109192), it is highly recommended to use Static Analysis.

The *biggest* problem is that Static Analysis is simply not used by most developers, especially those who are new to programming (usage tends to be higher by those writing well tested libraries).

And they really focus on other issues (type checking, basic logic flaws, code formatting, etc).

Those that do attempt to address injection vulnerabilities, do so via Taint Checking (see above), and are [often incomplete](https://github.com/vimeo/psalm/commit/2122e4a1756dac68a83ec3f5abfbc60331630781).

For a quick example, psalm, even in its strictest errorLevel (1), and/or running `--taint-analysis`, will not notice the missing quote marks in this SQL, and will incorrectly assume this is perfectly safe:

```php
$db = new mysqli('...');

$id = (string) ($_GET['id'] ?? 'id'); // Keep the type checker happy.

$db->prepare('SELECT * FROM users WHERE id = ' . $db->real_escape_string($id));
```

When psalm comes to taint checking the usage of a library (like Doctrine), it assumes all methods are safe, because none of them note the sinks (and even if they did, you're back to escaping being an issue).

## Proposal

This RFC proposes adding four functions:

* `is_literal(string $string): bool` to check if a variable represents a value written into the source code or not.

* `literal_implode(string $glue, array $pieces): string` - implode an array of literals, with a literal.
* `literal_concat(string $piece, string ...$pieces): string` - allow concatenating literal strings.
* `literal_sprintf(string $format, string ...$values): string` - a version of sprintf that uses literals.

A literal is defined as a value (string) which has been written by the programmer.

The value may be passed between functions, as long as it is not modified in any way.

```php
is_literal('Example'); // true

$a = 'Hello';
$b = 'World';

is_literal($a); // true
is_literal($a . $b); // true, details below.

$c = literal_concat($a, $b);
is_literal($c); // true, details below.

is_literal($_GET['id']); // false
is_literal('WHERE id = ' . intval($_GET['id'])); // false
is_literal(rand(0, 10)); // false
is_literal(sprintf('LIMIT %d', 3)); // false

function example($input) {
  if (!is_literal($input)) {
    throw new Exception('Non-literal detected!');
  }
  return $input;
}

example('hello'); // OK
example(example('hello')); // OK, still the same literal.
example(strtoupper('hello')); // Exception thrown.
```

There is no way to manually mark a string as a literal (i.e. no equivalent to `untaint()`); as soon as the value has been manipulated in any way, it is no longer marked as a literal.

## Previous Work

Google uses "compile time constants" in Go, which isn't as good as a run time solution (e.g. the `WHERE IN` issue), but it works, and is used by [go-safe-html](https://blogtitle.github.io/go-safe-html/) and [go-safesql](https://github.com/google/go-safeweb/tree/master/safesql).

Google also uses [Error Prone](https://errorprone.info/) in Java to augment the compiler's type analysis, where [@CompileTimeConstant](https://errorprone.info/bugpattern/CompileTimeConstant) ensures method parameters can only use "compile-time constant expressions" (this isn't a complete solution either).

Perl has a [Taint Mode](https://perldoc.perl.org/perlsec#Taint-mode), via the -T flag, where all input is marked as "tainted", and cannot be used by some methods (like commands that modify files), unless you use a regular expression to match and return known-good values (where regular expressions are easy to get wrong).

[JavaScript might get isTemplateObject](https://github.com/tc39/proposal-array-is-template-object) to "Distinguishing strings from a trusted developer from strings that may be attacker controlled" (intended to be [used with Trusted Types](https://github.com/mikewest/tc39-proposal-literals)).

As noted above, there is the [Taint extension for PHP](https://github.com/laruence/taint) by Xinchen Hui.

And there is the [Automatic SQL Injection Protection](https://wiki.php.net/rfc/sql_injection_protection) RFC by Matt Tait, where this RFC uses a similar concept of the [SafeConst](https://wiki.php.net/rfc/sql_injection_protection#safeconst). When Matt's RFC was being discussed, it was noted:

* "unfiltered input can affect way more than only SQL" ([Pierre Joye](https://news-web.php.net/php.internals/87355));
* this amount of work isn't ideal for "just for one use case" ([Julien Pauli](https://news-web.php.net/php.internals/87647));
* It would have effected every SQL function, such as `mysqli_query()`, `$pdo->query()`, `odbc_exec()`, etc (concerns raised by [Lester Caine](https://news-web.php.net/php.internals/87436) and [Anthony Ferrara](https://news-web.php.net/php.internals/87650));
* Each of those functions would need a bypass for cases where unsafe SQL was intentionally being used (e.g. phpMyAdmin taking SQL from POST data) because some applications intentionally "pass raw, user submitted, SQL" (Ronald Chmara [1](https://news-web.php.net/php.internals/87406)/[2](https://news-web.php.net/php.internals/87446)).

I also agree that "SQL injection is almost a solved problem [by using] prepared statements" ([Scott Arciszewski](https://news-web.php.net/php.internals/87400)), and this is where `is_literal()` can be used to check that no mistakes are made when using prepared statements.

## Usage

By libraries:

```php
class db {
  protected $level = 2; // Probably should default to 1 at first.
  function literal_check($var) {
    if (function_exists('is_literal') && !is_literal($var)) {
      if ($this->level === 0) {
        // Programmer aware, and is choosing to bypass this check.
      } else if ($this->level === 1) {
        trigger_error('Non-literal detected!', E_USER_WARNING);
      } else {
        throw new Exception('Non-literal detected!');
      }
    }
  }
  function unsafe_disable_injection_protection() {
    $this->level = 0;
  }
  function where($sql, $parameters = []) {
    $this->literal_check($sql);
    // ...
  }
}

$db->where('id = ?'); // OK
$db->where('id = ' . $_GET['id']); // Exception thrown
```

Table and Fields in SQL, which cannot use parameters; for example `ORDER BY`:

```php
$order_fields = [
    'name',
    'created',
    'admin',
  ];

$order_id = array_search(($_GET['sort'] ?? NULL), $order_fields);

$sql = literal_concat(' ORDER BY ', $order_fields[$order_id]);
```

Undefined number of parameters; for example `WHERE IN`:

```php
function where_in_sql($count) { // Should check for 0
  $sql = '?';
  for ($k = 1; $k < $count; $k++) {
    $sql .= ',?';
  }
  return $sql;
}
$sql = literal_concat('WHERE id IN (', where_in_sql(count($ids)), ')');
```

And for a real edge case, where the end user provides the table/field names. That would require the database abstraction to take those values separately, so it can ensure the marjority of the SQL is a literal, and it can check/escape those table/field names [basic example](https://gist.github.com/craigfrancis/901aa0479379fe9c261ccb2e33ebdcd7).

## Considerations

### Naming

Literal string is the standard name for strings in source code. See [Google](https://www.google.com/search?q=what+is+literal+string+in+php).

> A string literal is the notation for representing a string value within the text of a computer program. In PHP, strings can be created with single quotes, double quotes or using the heredoc or the nowdoc syntax...

Alternative suggestions have included `is_from_literal()` from [Jakob Givoni](https://news-web.php.net/php.internals/109197). I think `is_safe_string()` might be asking for trouble. Other terms have included "compile time constants" and "code string".

### Int/Float/Boolean Values.

When converting to string, they aren't guaranteed (and often don't) have the exact same value they have in source code.

For example, `TRUE` and `true` when cast to string give "1".

It's also a very low value feature, and there might not be space for a flag to be added.

### Performance

To do this testing, Máté Kocsis has created a [php benchmark](https://github.com/kocsismate/php-version-benchmarks/) to replicate the old [Intel Tests](https://01.org/node/3774).

The [preliminary testing on this implementation](https://github.com/craigfrancis/php-is-literal-rfc/blob/main/tests/results/with-concat/kocsismate.pdf) has found a 0.124% performance hit for the Laravel Demo app, 0.161% for Symfony (rounds 4-6, which involved 5000 requests). These tests do not connect to a database, as the variability introduced makes it impossible to measure the difference.

There is a more severe 3.719% when running this [concat test](https://github.com/kocsismate/php-version-benchmarks/blob/main/app/zend/concat.php#L25), which is not representative of a typical PHP script (it's not normal to concatenate 4 strings, 5 million times, with no other actions).

### String Concatenation

Dan Ackroyd has considered an approach that does not use string concatenation at run time. The intention was to reduce the performance impact even further; where the `literal_concat()` or `literal_implode()` support functions can make it easier for developers identify their mistakes.

Performance wise, I made up a test patch (not properly checked), to skip string concat at runtime, and with my own [simplistic testing](https://github.com/craigfrancis/php-is-literal-rfc/tree/main/tests) the [results](https://github.com/craigfrancis/php-is-literal-rfc/blob/main/tests/results/with-concat/local.pdf) found:

    Laravel Demo App: +0.30% with, vs +0.18% without concat.
    Symfony Demo App: +0.06% with, vs +0.06% without concat.
    My Concat Test:   +4.36% with, vs +2.23% without concat.
    -
    Website with 22 SQL queries: Inconclusive, too variable.

There is still a small impact without concat because the `concat_function()` in "zend_operators.c" uses `zend_string_extend()` (where the literal flag needs to be removed). And in "zend_vm_def.h", it has a similar version; and supports a quick concat with an empty string, which doesn't create a new variable (x2) and would need its flag removed as well.

Technically runtime concat isn't needed for most libraries, like an ORM or Query Builder, where their methods nearly always take a small literal string. But it would make adoption of `is_literal()` easier for existing projects that are currently using string concat for their SQL, HTML Snippets, etc.

Supporting runtime concat would make the is_literal() easier to understand, as it would be consistent with compiler vs runtime concat (because the compiler can sometimes concat strings, creating a single literal that would have the literal flag set).

As to helping developers identify their mistakes - by using `literal_concat()` or `literal_implode()`, Dan Ackroyd notes these functions would make it easier to identify where mistakes are made, rather than it being picked up at the end of a potentially long script, after multiple string concatenations, e.g.

```php
$sortOrder = 'ASC';

// 300 lines of code, or multiple function calls

$sql .= ' ORDER BY name ' . $sortOrder;

// 300 lines of code, or multiple function calls

$db->query($sql);
```

If a developer changed the literal `'ASC'` to `$_GET['order']`, the error raised by `$db->query()` would not be clear where the mistake was made. Whereas using `literal_concat()` would raise an exception, and highlight exactly where the issue happened:

```php
$sql = literal_concat($sql, ' ORDER BY name ', $sortOrder);
```

### Non Literal Values

As noted by [Dennis Birkholz](https://news-web.php.net/php.internals/87667), some Systems/Frameworks currently define certain variables (e.g. table name prefixes) without the use of a literal (e.g. ini/json/yaml).

And Larry Garfield notes that in Drupal's ORM "the table name itself is user-defined" (not in the PHP script).

While some systems will be able to use literals entirely, those that cannot will be able to use a Query Builder - one that validates the majority of its input are literals, and the exceptions can be accepted via appropriate validation (i.e. does this string match a known table name).

### Existing String Functions

Trying to determine if the `is_literal` flag should be passed through functions like `str_repeat()`, or `substr()` etc is difficult. Having a security feature be difficult to reason about, gives a much higher chance of mistakes.

For any use-case where dynamic strings are required, it would be better to build those strings with an appropriate query builder, or by using `literal_concat()`/`literal_implode()`.

## Backward Incompatible Changes

No known BC breaks, except for code-bases that already contain userland functions `is_literal()`, `literal_implode()` or `literal_concat()`.

## Proposed PHP Version(s)

PHP 8.1

## RFC Impact

### To SAPIs

None known

### To Existing Extensions

Not sure

### To Opcache

Not sure

## Open Issues

None

## Unaffected PHP Functionality

None known

## Future Scope

As noted by MarkR, the biggest benefit will come when it can be used by PDO and similar functions (`mysqli_query`, `preg_match`, `exec`, etc). But the basic idea can be used immediately by frameworks and general abstraction libraries, and they can give feedback for future work.

**Phase 2** could introduce a way for programmers to specify certain PHP function/method arguments can only accept literals, and/or specific value-objects their project trusts (this idea comes from [Trusted Types](https://web.dev/trusted-types/) in JavaScript).

For example, a project could require the second argument for `pg_query()` only accept literals or their `query_builder` object (which provides a `__toString` method); and that any output (print, echo, readfile, etc) must use the `html_output` object that's returned by their trusted HTML Templating system (using `ob_start()` might be useful here).

**Phase 3** could set a default of 'only literals' for all of the relevant PHP function arguments, so developers are given a warning, and later prevented (via an exception), when they provide an unsafe value to those functions (they could still specify that unsafe values are allowed, e.g. phpMyAdmin).

And, for a bit of silliness (Spaß ist verboten), MarkR would like a `is_figurative()` function (functionality to be confirmed).

## Proposed Voting Choices

Accept the RFC. Yes/No

## Implementation

[Available on GitHub](https://github.com/craigfrancis/php-src/tree/is_literal-with-functions).

It includes [Joe Watkin's implementation](https://github.com/php/php-src/compare/master...krakjoe:literals), which applies the literal flags, and supports string concat at runtime. And [Dan Ackroyd's implementation](https://github.com/php/php-src/compare/master...Danack:is_literal_attempt_two), which provides `literal_concat()` and `literal_implode()`.

## References

N/A

## Rejected Features

N/A

## Thanks

- **Dan Ackroyd**, DanAck, for starting the first implementation (which made this a reality), and followup on the version that provides `literal_concat()` and `literal_implode()`.
- **Joe Watkins**, krakjoe, for finding how to set the literal flag, and creating the implementation that supports string concat.
- **Máté Kocsis**, mate-kocsis, for setting up and doing the performance testing.
- **Rowan Tommins**, IMSoP, for re-writing this RFC to focus on the key features, and putting it in context of how it can be used by libraries.
- **Nikita Popov**, NikiC, for suggesting where the literal flag could be stored. Initially this was going to be the "GC_PROTECTED flag for strings", which allowed Dan to start the first implementation.
- **Mark Randall**, MarkR, for suggestions, and noting that "interned strings in PHP have a flag", which started the conversation on how this could be implemented.
- **Xinchen Hui**, who created the Taint Extension, allowing me to test the idea; and noting how Taint in PHP5 was complex, but "with PHP7's new zend_string, and string flags, the implementation will become easier" [source](https://news-web.php.net/php.internals/87396).
