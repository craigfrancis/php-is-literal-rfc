====== PHP RFC: Is Literal Check ======

  * Version: 0.6
  * Date: 2020-03-21
  * Updated: 2021-04-30
  * Author: Craig Francis, craig#at#craigfrancis.co.uk
  * Status: Draft
  * First Published at: https://wiki.php.net/rfc/is_literal
  * GitHub Repo: https://github.com/craigfrancis/php-is-literal-rfc

===== Introduction =====

A new function, //is_literal(string $string)//, to identify variables that have been created from a programmer defined string.

This takes the concept of "taint checking" and makes it simpler and stricter.

It does not allow a variable to be marked as untainted, and it does not allowing escaping (important).

For example, a database library that supports parametrised queries at the driver level, today a programmer could use either of these:

<code php>
$db->query('SELECT * FROM users WHERE id = ' . $_GET['id']); // INSECURE

$db->query('SELECT * FROM users WHERE id = ?', [$_GET['id']]);
</code>

By rejecting the SQL that was not written as a literal (first example), and only accepting a literal string (written by the programmer), the library can provide an "inherently safe API".

This definition of an "inherently safe API" comes from Christoph Kern, who did a talk in 2016 about [[https://www.youtube.com/watch?v=ccfEu-Jj0as|Preventing Security Bugs through Software Design]] (also at [[https://www.usenix.org/conference/usenixsecurity15/symposium-program/presentation/kern|USENIX Security 2015]]), which covers how this is used at Google. The idea is that we "Don't Blame the Developer, Blame the API"; where we need to put the burden on libraries (written once, used by many) to ensure that it's impossible for the developer to make these mistakes.

By adding a way for libraries to check if the strings they receive came from the developer (from trusted PHP source code), it allows the library to check they are being used in a safe way.

===== Why =====

The [[https://owasp.org/www-project-top-ten/|OWASP Top 10]] lists common vulnerabilities sorted by prevalence, exploitability, detectability, and impact.

**Injection vulnerabilities** remain at the top of the list (common prevalence, easy for attackers to detect/exploit, severe impact); and **XSS** comes in at 7 (widespread prevalence, easy for attackers to detect/exploit, moderate impact).

This is because injection mistakes are very easy to make, and hard to identify - is_literal() addresses this.

===== Examples =====

The [[https://www.doctrine-project.org/projects/doctrine-orm/en/current/reference/query-builder.html#high-level-api-methods|Doctrine Query Builder]] allows a custom WHERE clause to be provided as a string. This is intended for use with literals and placeholders, but does not protect against this simple mistake:

<code php>
// INSECURE
$qb->select('u')
   ->from('User', 'u')
   ->where('u.id = ' . $_GET['id'])
</code>

The definition of the //where()// method could check with //is_literal()// and throw an exception, advising the programmer to replace it with a safer use of placeholders:

<code php>
$qb->select('u')
   ->from('User', 'u')
   ->where('u.id = :identifier')
   ->setParameter('identifier', $_GET['id']);
</code>

Similarly, Twig allows [[https://twig.symfony.com/doc/2.x/recipes.html#loading-a-template-from-a-string|loading a template from a string]], which could allow accidentally skipping the default escaping functionality:

<code php>
// INSECURE
echo $twig->createTemplate('<p>Hi ' . $_GET['name'] . '</p>')->render();
</code>

If //createTemplate()// checked with //is_literal()//, the programmer could be advised to write this instead:

<code php>
echo $twig->createTemplate('<p>Hi {{ name }}</p>')->render(['name' => $_GET['name']]);
</code>

===== Failed Solutions =====

==== Education ====

Developer training has not worked, it simply does not scale (people start programming every day), and learning about every single issue is difficult.

Keeping in mind that programmers will frequently do just enough to complete their task (busy), where they often copy/paste a solution to their problem they find online (risky), modify it for their needs (risky), then move on.

We cannot keep saying they 'need to be careful', and relying on them to never make a mistake.

==== Escaping ====

Escaping is hard, and error prone.

We have a list of common [[https://github.com/craigfrancis/php-is-literal-rfc/blob/main/justification.md#common-mistakes|escaping mistakes]].

Developers should use parameterised queries (e.g. SQL), or a well tested library that knows how to escape values based on their context (e.g. HTML).

==== Taint Checking ====

Some languages implement a "taint flag" which tracks whether values are considered "safe".

There is a [[https://github.com/laruence/taint|Taint extension for PHP]] by Xinchen Hui, and [[https://wiki.php.net/rfc/taint|a previous RFC proposing it be added to the language]].

These solutions rely on the assumption that the output of an escaping function is safe for a particular context. This sounds reasonable in theory, but the operation of escaping functions, and the context for which their output is safe, are very hard to define. This leads to a feature that is both complex and unreliable.

This proposal avoids the complexity by addressing a different part of the problem: separating inputs supplied by the programmer, from inputs supplied by the user.

==== Static Analysis ====

While I agree with [[https://news-web.php.net/php.internals/109192|Tyson Andre]], it is highly recommended to use Static Analysis.

But they nearly always focus on other issues (type checking, basic logic flaws, code formatting, etc).

Those that attempt to address injection vulnerabilities, do so via Taint Checking (see above), and are [[https://github.com/vimeo/psalm/commit/2122e4a1756dac68a83ec3f5abfbc60331630781|often incomplete]].

For a quick example, psalm, even in its strictest errorLevel (1), and/or running //--taint-analysis//, will not notice the missing quote marks in this SQL, and will incorrectly assume this is perfectly safe:

<code php>
$db = new mysqli('...');

$id = (string) ($_GET['id'] ?? 'id'); // Keep the type checker happy.

$db->prepare('SELECT * FROM users WHERE id = ' . $db->real_escape_string($id));
</code>

When psalm comes to taint checking the usage of a library (like Doctrine), it assumes all methods are safe, because none of them note the sinks (and even if they did, you're back to escaping being an issue).

But the biggest problem is that Static Analysis is simply not used by most developers, especially those who are new to programming (usage tends to be higher by those writing well tested libraries).

===== Proposal =====

This RFC proposes adding three functions:

  * //is_literal(string $string): bool// to check if a variable represents a value written into the source code or not.
  * //literal_combine(string $piece, string $pieces): string// to allow concatenating literal strings.
  * //literal_implode(string $glue, array $pieces): string// to implode an array of literals, with a literal.

A literal is defined as a value (string) which has been written by the programmer. The value may be passed between functions, as long as it is not modified in any way.

<code php>
is_literal('Example'); // true

$a = 'Hello';
$b = 'World';

is_literal($a); // true
is_literal($a . $b); // false, no concat at run time (details below).

is_literal(literal_combine($a, $b)); // true

is_literal($_GET['id']); // false
is_literal('WHERE id = ' . intval($_GET['id'])); // false
is_literal(rand(0, 10)); // false
is_literal(sprintf('LIMIT %d', 3)); // false, should use parameters
</code>

There is no way to manually mark a string as a literal (i.e. no equivalent to //untaint()//); as soon as the value has been manipulated in any way, it is no longer marked as a literal.

*Technical detail: Strings that are concatenated in place at compile time are treated as a literal.*

===== Previous Work =====

Google uses "compile time constants" in Go, which isn't as good as a run time solution (e.g. the //WHERE IN// issue), but it works, and is used by [[https://blogtitle.github.io/go-safe-html/|go-safe-html]] and [[https://github.com/google/go-safeweb/tree/master/safesql|go-safesql]].

Google also uses [[https://errorprone.info/|Error Prone]] in Java to augment the compiler's type analysis, where [[https://errorprone.info/bugpattern/CompileTimeConstant|@CompileTimeConstant]] ensures method parameters can only use "compile-time constant expressions" (this isn't a complete solution either).

Perl has a [[https://perldoc.perl.org/perlsec#Taint-mode|Taint Mode]], via the -T flag, where all input is marked as "tainted", and cannot be used by some methods (like commands that modify files), unless you use a regular expression to match and return known-good values (where regular expressions are easy to get wrong).

[[https://github.com/tc39/proposal-array-is-template-object|JavaScript might get isTemplateObject]] to "Distinguishing strings from a trusted developer from strings that may be attacker controlled" (intended to be [[https://github.com/mikewest/tc39-proposal-literals|used with Trusted Types]]).

As noted above, there is the [[https://github.com/laruence/taint|Taint extension for PHP]] by Xinchen Hui.

And there is the [[https://wiki.php.net/rfc/sql_injection_protection|Automatic SQL Injection Protection]] RFC by Matt Tait, where this RFC uses a similar concept of the [[https://wiki.php.net/rfc/sql_injection_protection#safeconst|SafeConst]]. When Matt's RFC was being discussed, it was noted:

  * "unfiltered input can affect way more than only SQL" ([[https://news-web.php.net/php.internals/87355|Pierre Joye]]);
  * this amount of work isn't ideal for "just for one use case" ([[https://news-web.php.net/php.internals/87647|Julien Pauli]]);
  * It would have effected every SQL function, such as //mysqli_query()//, //$pdo->query()//, //odbc_exec()//, etc (concerns raised by [[https://news-web.php.net/php.internals/87436|Lester Caine]] and [[https://news-web.php.net/php.internals/87650|Anthony Ferrara]]);
  * Each of those functions would need a bypass for cases where unsafe SQL was intentionally being used (e.g. phpMyAdmin taking SQL from POST data) because some applications intentionally "pass raw, user submitted, SQL" (Ronald Chmara [[https://news-web.php.net/php.internals/87406|1]]/[[https://news-web.php.net/php.internals/87446|2]]).

I also agree that "SQL injection is almost a solved problem [by using] prepared statements" ([[https://news-web.php.net/php.internals/87400|Scott Arciszewski]]), and this is where //is_literal()// can be used to check that no mistakes are made when using prepared statements.

===== Usage =====

By libraries:

<code php>
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
</code>

Table and Fields in SQL, which cannot use parameters; for example //ORDER BY//:

<code php>
$order_fields = [
    'name',
    'created',
    'admin',
  ];

$order_id = array_search(($_GET['sort'] ?? NULL), $order_fields);

$sql = literal_combine(' ORDER BY ', $order_fields[$order_id]);
</code>

Undefined number of parameters; for example //WHERE IN//:

<code php>
function where_in_sql($count) { // Should check for 0
  $sql = [];
  for ($k = 0; $k < $count; $k++) {
    $sql[] = '?';
  }
  return literal_implode(',', $sql);
}
$sql = literal_combine('WHERE id IN (', where_in_sql(count($ids)), ')');
</code>

===== Considerations =====

==== Naming ====

Literal string is the standard name for strings in source code. See https://www.google.com/search?q=what+is+literal+string+in+php

> A string literal is the notation for representing a string value within the text of a computer program. In PHP, strings can be created with single quotes, double quotes or using the heredoc or the nowdoc syntax. ... The heredoc preserves the line breaks and other whitespace (including indentation) in the text.

Alternatives suggestions have included //is_from_literal()// from [[https://news-web.php.net/php.internals/109197|Jakob Givoni]]. I think //is_safe_string()// might be asking for trouble. Other terms have included "compile time constants" and "code string".

==== Supporting Int/Float/Boolean values. ====

When converting to string, they aren't guaranteed (and often don't) have the exact same value they have in source code.

For example, //TRUE// and //true// when cast to string give "1".

It's also a very low value feature, where there might not be space for a flag to be added.

==== Supporting Concatenation ====

Unfortunately early testing suggests there will be too much of a performance impact, and is why //literal_combine()// or //literal_implode()// exists.

It isn't needed for most libraries, like an ORM or Query Builder, where their methods nearly always take a small literal string.

It was considered because it would have made it easier for existing projects currently using string concatenation to adopt.

Joe Watkins has created a version that does support string concatenation, which we might re-consider at a later date (Joe's implementation also sets the literal flag, and was used as the basis for this implementation).

Máté Kocsis did the [[https://github.com/craigfrancis/php-is-literal-rfc/blob/main/tests/results/with-concat/kocsismate.pdf|primary testing on the string concat version]], and found a 0.124% performance hit for the Laravel Demo app, 0.161% for Symfony, and a more severe -3.719% when running this [[https://github.com/kocsismate/php-version-benchmarks/blob/main/app/zend/concat.php#L25|concat test]].

In my own [[https://github.com/craigfrancis/php-is-literal-rfc/tree/main/tests|simplistic testing]], the [[https://github.com/craigfrancis/php-is-literal-rfc/blob/main/tests/results/with-concat/local.pdf|results]] found a 0.42% performance hit for the Laravel Demo app, 0.40% for Symfony, and 1.81% when running my [[https://github.com/craigfrancis/php-is-literal-rfc/blob/main/tests/001.phpt|concat test]] via //./cli/php//.

Dan Ackroyd also notes that the use of //literal_combine()// or //literal_implode()// will make it easier to identify exactly where mistakes are made, rather than it being picked up at the end of a potentially long script, after multiple string concatenations, e.g.

<code php>
$sortOrder = 'ASC';

// 300 lines of code, or multiple function calls

$sql .= ' ORDER BY name ' . $sortOrder;

// 300 lines of code, or multiple function calls

$db->query($sql);
</code>

If a developer changed the literal //'ASC'// to //$_GET['order']//, the error raised by //$db->query()// would not be clear where the mistake was made. Whereas using //literal_combine()// highlights exactly where the issue happened:

<code php>
$sql = literal_combine($sql, ' ORDER BY name ', $sortOrder);
</code>

==== Performance ====

The proposed implementation has:

    [TODO]

Also, see the section above, where string concatenation support would have introduced a ~1% performance hit.

==== Values from INI/JSON/YAML ====

As noted by [[https://news-web.php.net/php.internals/87667|Dennis Birkholz]], Systems/Frameworks that define certain variables (e.g. table name prefixes) without the use of a literal (e.g. ini/json/yaml files), might need to make some changes to use this feature (depending on where they use the is_literal check).

==== Existing String Functions ====

Trying to determine if the //is_literal// flag should be passed through functions like //str_repeat()//, or //substr()// etc is difficult. Having a security feature be difficult to reason about, gives a much higher chance of making a mistake.

For any use-case where dynamic strings are required, it would be better to build those strings with an appropriate query builder, or by using //literal_combine()/////literal_implode()//.

===== Backward Incompatible Changes =====

No known BC breaks, except for code-bases that already contain userland functions //is_literal()//, //literal_implode()// or //literal_combine()//.

===== Proposed PHP Version(s) =====

PHP 8.1

===== RFC Impact =====

==== To SAPIs ====

None known

==== To Existing Extensions ====

Not sure

==== To Opcache ====

Not sure

===== Open Issues =====

None

===== Unaffected PHP Functionality =====

None known

===== Future Scope =====

As noted by MarkR, the biggest benefit will come when it can be used by PDO and similar functions (//mysqli_query//, //preg_match//, //exec//, etc). But the basic idea can be used immediately by frameworks and general abstraction libraries, and they can give feedback for future work.

**Phase 2** could introduce a way for programmers to specify certain PHP function/method arguments can only accept literals, and/or specific value-objects their project trusts (this idea comes from [[https://web.dev/trusted-types/|Trusted Types]] in JavaScript).

For example, a project could require the second argument for //pg_query()// only accept literals or their //query_builder// object (which provides a //__toString// method); and that any output (print, echo, readfile, etc) must use the //html_output// object that's returned by their trusted HTML Templating system (using //ob_start()// might be useful here).

**Phase 3** could set a default of 'only literals' for all of the relevant PHP function arguments, so developers are given a warning, and later prevented (via an exception), when they provide an unsafe value to those functions (they could still specify that unsafe values are allowed, e.g. phpMyAdmin).

And, for a bit of silliness (Spaß ist verboten), MarkR would like a //is_figurative()// function (functionality to be confirmed).

===== Proposed Voting Choices =====

Accept the RFC. Yes/No

===== Patches and Tests =====

N/A

===== Implementation =====

Dan Ackroyd has [[https://github.com/php/php-src/compare/master...Danack:is_literal_attempt_two|started an implementation]], which uses functions like [[https://github.com/php/php-src/compare/master...Danack:is_literal_attempt_two#diff-2b0486443df74cd919c949f33f895eacf97c34b8490e7554e032e770ab11e4d8R2761|literal_combine()]] to avoid performance concerns.

Joe Watkins has [[https://github.com/php/php-src/compare/master...krakjoe:literals|created an implementation]] which includes string concat. As noted above, the performance impact is probably too much for it to be accepted.

===== References =====

N/A

===== Rejected Features =====

N/A

===== Thanks =====

  - **Dan Ackroyd**, DanAck, for starting the first implementation (which made this a reality), and followup on the version that uses functions instead of string concat.
  - **Joe Watkins**, krakjoe, for finding how to set the literal flag (tricky), and creating the implementation that does support string concat.
  - **Rowan Tommins**, IMSoP, for re-writing this RFC to focus on the key features, and putting it in context of how it can be used by libraries.
  - **Nikita Popov**, NikiC, for suggesting where the literal flag could be stored. Initially this was going to be the "GC_PROTECTED flag for strings", which allowed Dan to start the first implementation.
  - **Mark Randall**, MarkR, for alternative ideas, and noting that "interned strings in PHP have a flag", which started the conversation on how this could be implemented.
  - **Xinchen Hui**, who created the Taint Extension, allowing me to test the idea; and noting how Taint in PHP5 was complex, but "with PHP7's new zend_string, and string flags, the implementation will become easier" [[https://news-web.php.net/php.internals/87396|source]].
