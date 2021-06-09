# PHP RFC: Is Literal Check

* Version: 0.8
* Date: 2020-03-21
* Updated: 2021-06-06
* Author: Craig Francis, craig#at#craigfrancis.co.uk
* Contributors: Joe Watkins, Dan Ackroyd, Máté Kocsis
* Status: Draft
* First Published at: https://wiki.php.net/rfc/is_literal
* GitHub Repo: https://github.com/craigfrancis/php-is-literal-rfc

## Introduction

Add the function `is_literal(string $string)`, so strings can be tested to ensure they were written by the developer (defined in the PHP source code, not containing any user input).

This flag provides a lightweight, simple, and very effective way to identify common Injection Vulnerabilities.

It avoids the "false sense of security" that comes with the flawed "Taint Checking" approach, [because escaping is very difficult to get right](https://github.com/craigfrancis/php-is-literal-rfc/blob/main/justification/escaping.php?ts=4).

Developers should not escape anything themselves; they should use parameterised queries, and/or well-tested libraries.

These libraries require some strings to only come from the developer; but because it's [easy to incorrectly include user values](https://github.com/craigfrancis/php-is-literal-rfc/blob/main/justification/mistakes.php?ts=4), Injection Vulnerabilities are still introduced by the thousands of developers using these libraries incorrectly. You will notice the linked examples are based on examples found in the Libraries' official documentation, they still "work", and are typically shorter/easier than doing it correctly (I've found many of them on live websites, and it's why I'm here). A simple Query Builder example being:

```php
$qb->select('u')
   ->from('User', 'u')
   ->where('u.id = ' . $_GET['id']); // INSECURE
```

The "Future Scope" section explains how native functions will be able to use `is_literal()`.

## Background

### The Problem

Injection and Cross-Site Scripting (XSS) vulnerabilities are **easy to make**, **hard to identify**, and **very common**.

We like to think every developer reads the documentation, and would never ever directly include (inject) user values into their SQL/HTML/CLI - but we all know that's not the case.

It's why these two issues have **always** been on the [OWASP Top 10](https://owasp.org/www-project-top-ten/); a list designed to raise awareness of common issues, ranked on their prevalence, exploitability, detectability, and impact:

|  Year           |  Injection Position  |  XSS Position  |
| --------------- | -------------------- | -------------- |
|  2017 - Latest  |  **1**               |  7             |
|  2013           |  **1**               |  3             |
|  2010           |  **1**               |  2             |
|  2007           |  2                   |  **1**         |
|  2004           |  6                   |  4             |
|  2003           |  6                   |  4             |

### Usage Elsewhere

Google are already using this concept with their **Go** and **Java** libraries, and it's been very effective.

Christoph Kern (Information Security Engineer at Google) did a talk in 2016 about [Preventing Security Bugs through Software Design](https://www.youtube.com/watch?v=ccfEu-Jj0as) (also at [USENIX Security 2015](https://www.usenix.org/conference/usenixsecurity15/symposium-program/presentation/kern)), pointing out the need for developers to use libraries (like [go-safe-html](https://blogtitle.github.io/go-safe-html/) and [go-safesql](https://github.com/google/go-safeweb/tree/master/safesql)) to do the encoding, where they **only accept strings written by the developer** (literals). This ensures the thousands of developers using these libraries cannot introduce Injection Vulnerabilities.

It's been so successful Krzysztof Kotowicz (Information Security Engineer at Google, or "Web security ninja") is now adding it to **JavaScript** (details below).

### Usage in PHP

Libraries would be able to use `is_literal()` immediately, allowing them to warn developers about Injection Issues as soon as they receive any non-literal strings, for example:

**Propel** (Mark Scherer): "given that this would help to more safely work with user input, I think this syntax would really help in Propel."

**RedBean** (Gabor de Mooij): "You can list RedBeanPHP as a supporter, we will implement this into the core."

## Proposal

Add `is_literal(string $string): bool` to check if a variable contains a string defined in the PHP script.

```php
is_literal('Example'); // true

$a = 'Hello';
$b = 'World';

is_literal($a); // true
is_literal($a . $b); // true
is_literal("Hi $b"); // true

is_literal($_GET['id']); // false
is_literal(rand(0, 10)); // false
is_literal(sprintf('Example %d', true)); // false
is_literal('/bin/rm -rf ' . $_GET['path']); // false
is_literal('<img src=' . htmlentities($_GET['src']) . ' />'); // false
is_literal('WHERE id = ' . $db->real_escape_string($_GET['id'])); // false
is_literal(sprintf('LIMIT %d', 3)); // false

function example($input) {
  if (!is_literal($input)) {
    throw new Exception('Non-literal detected!');
  }
  return $input;
}

example($a); // OK
example(example($a)); // OK, still the same literal.
example(strtoupper($a)); // Exception thrown.
```

Because so much existing code uses string concatenation, and because it does not modify what the programmer has written, concatenated literals will keep the literal flag. This includes the use of `str_repeat()`, `str_pad()`, `implode()`, `join()`, `array_pad()`, and `array_fill()`.

## Try it

[Have a play with it on 3v4l.org](https://github.com/craigfrancis/php-is-literal-rfc/blob/main/justification/usage.php?ts=4)

[How it can be used by libraries](https://github.com/craigfrancis/php-is-literal-rfc/blob/main/justification/example.php?ts=4) - Notice how this example library provides an `unsafe_sql` value-object to bypass the `is_literal()` check, but doesn't need to use it (it's useful as a temporary thing, but there are much safer/better solutions, which developers are/should already be using). And note how it just raises a warning, to simply let the developer know about the issue, **without breaking anything.**

## FAQ's

### Taint Checking

**Taint checking is flawed, isn't this the same?** It is not the same.

Taint Checking incorrectly assumes the output of an escaping function is "safe" for a particular context. While it sounds reasonable in theory, the operation of escaping functions, and the context for which their output is safe, are very hard to define. This leads to a feature that is both complex and unreliable.

```php
$sql = 'SELECT * FROM users WHERE id = ' . $db->real_escape_string($id); // INSECURE
$html = "<img src=" . htmlentities($url) . " alt='' />"; // INSECURE
$html = "<a href='" . htmlentities($url) . "'>..."; // INSECURE
```

All three examples would be incorrectly considered "untainted". The first two need the values to be quoted. The third example, `htmlentities()` does not escape single quotes by default before PHP 8.1 ([fixed](https://github.com/php/php-src/commit/50eca61f68815005f3b0f808578cc1ce3b4297f0)), and it does not consider the issue of 'javascript:' URLs.

In comparison, `is_literal()` doesn't have an equivalent of `untaint()`, or support escaping. Instead PHP will set the literal flag, and as soon as the value has been manipulated or includes anything that is not from a literal (e.g. user data), the literal flag is lost.

This allows libraries to raise a warning whenever any of the thousands of developers use them incorrectly (not currently possible), where the library will handle all of the escaping (making the implementation of `is_literal()` much simpler and reliable). And as noted in the "Future Scope" section, native functions will be able to use the literal flag as well.

### Education

**Why not educate everyone?** You can't - developer training simply does not scale, and mistakes still happen.

We cannot expect everyone to have formal training, know everything from day 1, and consider programming a full time job. We want new programmers, with a variety of experiences, ages, and backgrounds. Everyone should be guided to do the right thing, and notified as soon as they make a mistake (we all make mistakes). We also need to acknowledge that many programmers are busy, do copy/paste code, don't necessarily understand what it does, edit it for their needs, then simply move on to their next task.

### Static Analysis

**Why not use static analysis?** It will never be used by most developers.

I still agree with [Tyson Andre](https://news-web.php.net/php.internals/109192), you should use Static Analysis, but it's an extra step that most programmers cannot be bothered to do, especially those who are new to programming (its usage tends to be higher among those writing well-tested libraries).

Also, these tools currently focus on other issues (type checking, basic logic flaws, code formatting, etc), rarely attempting to address injection vulnerabilities. Those that do are [often incomplete](https://github.com/vimeo/psalm/commit/2122e4a1756dac68a83ec3f5abfbc60331630781), need sinks specified on all library methods (unlikely to happen), and are not enabled by default. For example, Psalm, even in its strictest errorLevel (1), and running `--taint-analysis` (I bet you don't use this), it will not notice the missing quote marks in this SQL, and incorrectly assume it's safe:

```php
$db = new mysqli('...');

$id = (string) ($_GET['id'] ?? 'id'); // Keep the type checker happy.

$db->prepare('SELECT * FROM users WHERE id = ' . $db->real_escape_string($id)); // INSECURE
```

### Performance

**What about the performance impact?** Máté Kocsis has created a [php benchmark](https://github.com/kocsismate/php-version-benchmarks/) to replicate the old [Intel Tests](https://01.org/node/3774), and the [preliminary testing on this implementation](https://github.com/craigfrancis/php-is-literal-rfc/blob/main/tests/results/with-concat/kocsismate.pdf) has found a 0.124% performance hit for the Laravel Demo app, and 0.161% for Symfony (rounds 4-6, which involved 5000 requests). These tests do not connect to a database, as the variability introduced makes it impossible to measure the difference.

There is a more severe 3.719% when running this [concat test](https://github.com/kocsismate/php-version-benchmarks/blob/main/app/zend/concat.php#L25), but this is not representative of a typical PHP script (it's not normal to concatenate 4 strings, 5 million times, with no other actions).

Joe Watkins has also noted that further optimisations are possible (the implementation has focused on making it work).

### String Concatenation

**Why does this support concatenation?** Technically concat support isn't needed for most libraries, like an ORM or Query Builder, where their methods nearly always take small literal strings. But it does make the adoption of `is_literal()` easier for existing projects that are currently using string concat for their SQL/HTML/CLI/etc.

Dan Ackroyd has considered an approach that does not use string concatenation at run time. The intention was to reduce the performance impact even further; and by introducing `literal_concat()` or `literal_implode()` support functions, it would make it easier for developers to identify their mistakes.

Performance wise, I made up a test patch (not properly checked), to skip string concat at runtime, and with my own [simplistic testing](https://github.com/craigfrancis/php-is-literal-rfc/tree/main/tests) the [results](https://github.com/craigfrancis/php-is-literal-rfc/blob/main/tests/results/with-concat/local.pdf) found:

    Laravel Demo App: +0.30% with, vs +0.18% without concat.
    Symfony Demo App: +0.06% with, vs +0.06% without concat.
    My Concat Test:   +4.36% with, vs +2.23% without concat.
    -
    Website with 22 SQL queries: Inconclusive, too variable.

There is still a small impact without concat support as the `concat_function()` in "zend_operators.c" uses `zend_string_extend()` which needs to remove the literal flag. And in "zend_vm_def.h", it has a similar version; and supports a quick concat with an empty string, which doesn't create a new variable (x2) and would need its flag removed as well.

Also, supporting runtime concat would make `is_literal()` easier to understand, as the compiler can sometimes concat strings (making a single literal), making it appear that concat works in some cases but not others.

### String Splitting

**Why don't you support string splitting?** In short, we can't find any use cases (security features should try to keep the implementation as simple as possible).

Also, the security considerations are different. Concatenating joins known known/fixed units together, whereas if you're starting with a "developer created string" (which is trusted), and the program allows the evil user to split the string (e.g. setting the length in substr), then they get considerable control over the result (it creates an untrusted modification).

These are unlikely to be written by a programmer, but consider these:

```php
$url  = substr('https://example.com/js/a.js?v=2', 0, $length);
$html = substr('<a href="#">#</a>', 0, $length);
```

If that URL was used in a Content-Security-Policy, then it's necessary to remove the query string, but as more of the string is removed, the more resources can be included ("https:" basically allows resources from anywhere). With the HTML example, moving from the tag content to the attribute can be a problem (technically the HTML Templating Engine should be fine, but unfortunately libraries like Twig are not currently context aware, so you need to change from the default 'html' encoding to explicitly using 'html_attr' encoding).

Or in other words; trying to determine if the `literal` flag should be passed through functions like `substr()` is difficult. Having a security feature be difficult to reason about, gives a much higher chance of mistakes.

Krzysztof Kotowicz has confirmed that, at Google, with "go-safe-html", splitting is explicitly not supported because it "can cause issues"; for example, "arbitrary split position of a HTML string can change the context".

### WHERE IN

**How can this work with an undefined number of parameters, for example `WHERE id IN (?, ?, ?)`?** You should already be following the advice from [Levi Morrison](https://stackoverflow.com/a/23641033/538216), [PDO Execute](https://www.php.net/manual/en/pdostatement.execute.php#example-1012), and [Drupal Multiple Arguments](https://www.drupal.org/docs/7/security/writing-secure-code/database-access#s-multiple-arguments):

```php
$sql = 'WHERE id IN (' . join(',', array_fill(0, count($ids), '?')) . ')';
```

Or, if you prefer to use concatenation:

```php
$sql = '?';
for ($k = 1; $k < $count; $k++) {
  $sql .= ',?';
}
```

This pushes everyone to use parameters properly; rather than using implode() on user values, and including them directly in the SQL (which is easy to get wrong).

### Non-Parameterised Values

**How can this work with Table and Field names in SQL, which cannot use parameters?** They are often in variables written as literals anyway (so no changes needed); and if they are dependent on user input, you *can* and *should* use literals:

```php
$order_fields = [
    'name',
    'created',
    'admin',
  ];

$order_id = array_search(($_GET['sort'] ?? NULL), $order_fields);

$sql .= ' ORDER BY ' . $order_fields[$order_id];
```

By using an allow-list, we ensure the user (attacker) cannot use anything unexpected.

### Non-Literal Values

**How does this work in cases where you can't use literals?**

For example [Dennis Birkholz](https://news-web.php.net/php.internals/87667) noted that some Systems/Frameworks currently define some variables (e.g. table name prefixes) without the use of a literal (e.g. ini/json/yaml).

And Larry Garfield noted that in Drupal's ORM "the table name itself is user-defined" (not in the PHP script).

While most systems can use literals entirely, these special values should still be handled separately. This allows the library to ensure the majority of the input (SQL) is a literal, then it can consistently check/escape those special values (e.g. does it match a valid table/field name, which can be included safely).

[How this can be done](https://github.com/craigfrancis/php-is-literal-rfc/blob/main/justification/example.php?ts=4#L194).

### Naming

**Why is it called is_literal()?** A "Literal String" is the standard name for strings in source code. See [Google](https://www.google.com/search?q=what+is+literal+string+in+php).

> A string literal is the notation for representing a string value within the text of a computer program. In PHP, strings can be created with single quotes, double quotes or using the heredoc or the nowdoc syntax...

Alternative suggestions have included `is_from_literal()` from [Jakob Givoni](https://news-web.php.net/php.internals/109197), and references to alternative implementations like "compile time constants" and "code string".

We cannot call it `is_safe_string()`, because we cannot say that a string is safe:

```php
$cli = 'rm -rf ?';
$sql = 'DELETE FROM my_table WHERE my_date >= ?';
eval('$name = "' . $_GET['name'] . '";'); // INSECURE
```

While the first two cannot include Injection Vulnerabilities, the parameters could be set to "/" or "0000-00-00" (providing a nice vanishing magic trick); and the last one, well, they have much bigger issues to worry about (it's clearly irresponsible, and intentionally dangerous).

Also, this name is also unlikely to clash with any userland functions.

### Support Functions

**What about other support functions?** Dan Ackroyd proposed the `literal_concat()` and `literal_implode()` functions, which can be created as userland functions.

Developers may want to create and use these to help identify exactly where mistakes are made, for example:

```php
$sortOrder = 'ASC';

// 300 lines of code, or multiple function calls

$sql .= ' ORDER BY name ' . $sortOrder;

// 300 lines of code, or multiple function calls

$db->query($sql);
```

If a developer changed the literal `'ASC'` to `$_GET['order']`, the error would be noticed by `$db->query()`, but it's not clear where the mistake was made. Whereas, if they were using `literal_concat()`, that would raise an exception much earlier, and highlight exactly where the mistake happened:

```php
$sql = literal_concat($sql, ' ORDER BY name ', $sortOrder);
```

### Int/Float/Boolean Values.

**Why don't you support values other than strings?** It's a very low value feature. And when converting these values to a string, they aren't guaranteed (and often don't) have the exact same value they have in source code. e.g. `TRUE` and `true` when cast to a string give "1".

### Extensions

**Extensions create and manipulate strings, won't this break the literal flag?** Strings have multiple flags already, and are off by default, this is the correct behaviour when extensions create their own strings (should not be considered a literal). If an extension is found to be changing the literal flag incorrectly (unlikely), that's the same as any new flag being introduced, and will need to be fixed in the same way.

### Reflection API

**Why don't you use the reflection API?** It currently allows you to "introspect classes, interfaces, functions, methods and extensions"; it's not currently set up for object methods to inspect the code calling it. Even if that was to be added (unlikely), it could only check if the literal was defined there, it couldn't handle variables (tracking back to their source), nor could it provide any future scope for these checks happening in native functions (see "Future Scope").

## Previous Work

**Go** programs can use "ScriptFromConstant" to express the concept of a "compile time constant" ([more details](https://blogtitle.github.io/go-safe-html/)).

**Java** can use [Error Prone](https://errorprone.info/) with [@CompileTimeConstant](https://errorprone.info/bugpattern/CompileTimeConstant) to ensure method parameters can only use "compile-time constant expressions".

**JavaScript** is getting [isTemplateObject](https://github.com/tc39/proposal-array-is-template-object), for "Distinguishing strings from a trusted developer from strings that may be attacker controlled" (intended to be [used with Trusted Types](https://github.com/mikewest/tc39-proposal-literals)).

**Perl** has a [Taint Mode](https://perldoc.perl.org/perlsec#Taint-mode), via the -T flag, where all input is marked as "tainted", and cannot be used by some methods (like commands that modify files), unless you use a regular expression to match and return known-good values (where regular expressions are easy to get wrong).

There is a [Taint extension for PHP](https://github.com/laruence/taint) by Xinchen Hui, and [a previous RFC proposing it be added to the language](https://wiki.php.net/rfc/taint) by Wietse Venema.

And there is the [Automatic SQL Injection Protection](https://wiki.php.net/rfc/sql_injection_protection) RFC by Matt Tait (this RFC uses a similar concept of the [SafeConst](https://wiki.php.net/rfc/sql_injection_protection#safeconst)). When Matt's RFC was being discussed, it was noted:

* "unfiltered input can affect way more than only SQL" ([Pierre Joye](https://news-web.php.net/php.internals/87355));
* this amount of work isn't ideal for "just for one use case" ([Julien Pauli](https://news-web.php.net/php.internals/87647));
* It would have effected every SQL function, such as `mysqli_query()`, `$pdo->query()`, `odbc_exec()`, etc (concerns raised by [Lester Caine](https://news-web.php.net/php.internals/87436) and [Anthony Ferrara](https://news-web.php.net/php.internals/87650));
* Each of those functions would need a bypass for cases where unsafe SQL was intentionally being used (e.g. phpMyAdmin taking SQL from POST data) because some applications intentionally "pass raw, user submitted, SQL" (Ronald Chmara [1](https://news-web.php.net/php.internals/87406)/[2](https://news-web.php.net/php.internals/87446)).

All of these concerns have been addressed by `is_literal()`.

I also agree with [Scott Arciszewski](https://news-web.php.net/php.internals/87400), "SQL injection is almost a solved problem [by using] prepared statements", where `is_literal()` is essential for identifying the mistakes that are still being made.

## Backward Incompatible Changes

No known BC breaks, except for code-bases that already contain the userland function `is_literal()` which is unlikely.

## Proposed PHP Version(s)

PHP 8.1

## RFC Impact

### To SAPIs

None known

### To Existing Extensions

None known

### To Opcache

None known

## Open Issues

None

## Unaffected PHP Functionality

None known

## Future Scope

As noted by MarkR, the biggest benefit will come when this flag can be used by PDO and similar functions (`mysqli_query`, `preg_match`, `exec`, etc).

First we need libraries to start using `is_literal()` to check their inputs, and use the appropriate escaping. This can result in strings that are no longer literals, but can still be trusted.

Then, with a future RFC, we can introduce checks for the native functions. By using the [Trusted Types](https://web.dev/trusted-types/) concept from JavaScript (which protects [60+ Injection Sinks](https://www.youtube.com/watch?v=po6GumtHRmU&t=92s), like innerHTML), the libraries create a stringable object as their output. These objects would be marked as "trusted" (because the library is sure they do not contain any Injection Vulnerabilities). The native functions can then **warn** developers when they do not receive a literal, or one of these trusted objects. These warnings would not **break anything**, they just make developers aware of the mistakes they have made.

## Proposed Voting Choices

Accept the RFC. Yes/No

## Implementation

[Joe Watkin's implementation](https://github.com/php/php-src/compare/master...krakjoe:literals)

## References

N/A

## Rejected Features

N/A

## Thanks

- **Dan Ackroyd**, DanAck, for starting the [first implementation](https://github.com/php/php-src/compare/master...Danack:is_literal_attempt_two), which made this a reality, providing `literal_concat()` and `literal_implode()`, and followup on how it should work.
- **Joe Watkins**, krakjoe, for finding how to set the literal flag, and creating the implementation that supports string concat.
- **Máté Kocsis**, mate-kocsis, for setting up and doing the performance testing.
- **Xinchen Hui**, who created the Taint Extension, allowing me to test the idea; and noting how Taint in PHP5 was complex, but "with PHP7's new zend_string, and string flags, the implementation will become easier" [source](https://news-web.php.net/php.internals/87396).
- **Rowan Francis**, for proof-reading, and helping me make an RFC that contains readable English.
- **Rowan Tommins**, IMSoP, for re-writing this RFC to focus on the key features, and putting it in context of how it can be used by libraries.
- **Nikita Popov**, NikiC, for suggesting where the literal flag could be stored. Initially this was going to be the "GC_PROTECTED flag for strings", which allowed Dan to start the first implementation.
- **Mark Randall**, MarkR, for suggestions, and noting that "interned strings in PHP have a flag", which started the conversation on how this could be implemented.
- **Sara Golemon**, SaraMG, for noting how this RFC had to explain how `is_literal()` is different to the flawed Taint Checking approach, so we don't get "a false sense of security or require far too much escape hatching".
