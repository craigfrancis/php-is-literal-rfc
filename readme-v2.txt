====== PHP RFC: LiteralString ======

  * Version: 2.0
  * Voting Start: ???
  * Voting End: ???
  * RFC Started: 2022-12-27
  * RFC Updated: 2022-12-27
  * Author: Craig Francis, craig#at#craigfrancis.co.uk
  * Contributors: Joe Watkins, Máté Kocsis
  * Status: Draft
  * First Published at: https://wiki.php.net/rfc/literal_string
  * GitHub Repo: https://github.com/craigfrancis/php-is-literal-rfc/blob/main/readme-v2.md
  * Implementation: https://github.com/php/php-src/compare/master...krakjoe:literals

===== Introduction =====

Add //LiteralString// type, and //is_literal_string()//, to check that a variable contains a "developer defined string".

This ensures the value cannot be a source of an Injection Vulnerability, because it does not contain user input.

This technique is used at Google (as described in "Building Secure and Reliable Systems", see [[https://static.googleusercontent.com/media/sre.google/en//static/pdf/building_secure_and_reliable_systems.pdf#page=287|Common Security Vulnerabilities, pages 251-255]], which shows how "developer-controlled input" prevents these issues in Go); it's used by FaceBook developers (ref [[https://eiv.dev/python-pyre/|pyre type-checker]], which has been added to Python 3.11 via [[https://peps.python.org/pep-0675/|PEP 675]]); and Christoph Kern discussed it in 2016 with [[https://www.youtube.com/watch?v=ccfEu-Jj0as|Preventing Security Bugs through Software Design]]. Also explained at [[https://www.usenix.org/conference/usenixsecurity15/symposium-program/presentation/kern|USENIX Security 2015]], [[https://www.youtube.com/watch?v=06_suQAAfBc|OWASP AppSec US 2021]], and summarised at [[https://eiv.dev/|eiv.dev]].

===== The Problem =====

Injection and Cross-Site Scripting (XSS) vulnerabilities are **easy to make**, **hard to identify**, and **very common**.

<code php>
// Doctrine
$qb->select('u')
   ->from('User', 'u')
   ->where('u.id = ?1')
   ->setParameter(1, $_GET['id']); // Correct

$qb->select('u')
   ->from('User', 'u')
   ->where('u.id = ' . $_GET['id']); // INSECURE, but easier to write/read :-)

$qb->select('u')
    ->from('User', 'u')
    ->where($qb->expr()->andX(
        $qb->expr()->eq('u.type_id', $_GET['type']), // INSECURE, 'u.type_id) OR (1 = 1'
        $qb->expr()->isNull('u.deleted'), // Is ignored due to 'OR'
    ));

// Laravel
DB::table('user')->whereRaw('CONCAT(name_first, " ", name_last) LIKE "' . $search . '%"');
DB::table('user')->whereRaw('CONCAT(name_first, " ", name_last) LIKE ?', $search . '%'); // INSECURE
</code>

[[https://github.com/craigfrancis/php-is-literal-rfc/blob/main/justification/mistakes.php|Additional Examples]]; where tools (e.g. SQL Map, Havij, jSQL) make it easy to exploit these mistakes.

In the latest [[https://owasp.org/www-project-top-ten/|OWASP Top 10]], Injection Vulnerabilities rank third highest security risk to web applications (database abstractions have at least helped move them from the top spot, but do not solve the problem).

^  Year           ^  Injection Position  ^  XSS Position  ^
|  2021 - Latest  |  3                   |  3             |
|  2017           |  1                   |  7             |
|  2013           |  1                   |  3             |
|  2010           |  1                   |  2             |
|  2007           |  2                   |  1             |
|  2004           |  6                   |  4             |
|  2003           |  6                   |  4             |

===== Proposal =====

A string will be of the LiteralString type if it was defined by the programmer (in source code), or is the result of LiteralString values being concatenated. The LiteralString type is lost when the value is modified.

The following string concatenation functions can return LiteralString values:

  - //str_repeat()//
  - //str_pad()//
  - //implode()//
  - //join()//

Namespaces constructed for the programmer by the compiler will be marked as a LiteralString.

==== Examples ====

<code php>
$a = 'Hello';
$b = 'World';

is_literal_string('Example'); // true
is_literal_string($a); // true
is_literal_string($_GET['id']); // false
</code>

<code php>
function example1(LiteralString $input) {
  return $input;
}

function example2(String $input) {
  if (!is_literal_string($input)) {
    error_log('Log issue, but still continue.');
  }
  return $input;
}

example1($a); // OK
example1($a . $b); // OK
example1("Hi $b"); // OK
example1(example1($a)); // OK

example1($_GET['id']); // TypeError
example1('/bin/rm -rf ' . $_GET['path']); // TypeError
example1('<img src=' . $_GET['src'] . ' />'); // TypeError
example1('WHERE id = ' . $_GET['id']); // TypeError
</code>

Most libraries will probably use something like //example2()// to test the values they receive, partially for backwards compatibility reasons (can use //function_exists//), but also because it allows them to easily choose how mistakes are handled. For example, I would suggest libraries used logged warnings by default, with an option to throw exceptions for those developers who are confident their code is ready or when it's in development mode, or they could provide a way to disable checks on a per query basis, or entirely for legacy projects ([[https://github.com/craigfrancis/php-is-literal-rfc/blob/main/justification/example.php?ts=4|example]]).

Libraries could also check their output (e.g. SQL to a database) is still a LiteralString, but this isn't a priority (libraries are rarely the source of Injection Vulnerabilities, it's usually the developer using them incorrectly).

You can test it at [[https://3v4l.org/sLmC9/rfc#vrfc.literals|3v4l.org]] using the previous "is_literal()" function name.

<code php>
class sqli_protected_db {
  private $db;
  public function __construct() {
    $this->db = new mysqli('localhost', 'username', 'password', 'database');
  }
  public function query(LiteralString $sql, Array $parameters = [], Array $aliases = []) {
    foreach ($aliases as $name => $value) {
      $sql = str_replace('{' . $name . '}', '`' . str_replace('`', '``', $value) . '`', $sql);
    }
    print_r($sql . "\n");
    print_r(iterator_to_array($this->db->execute_query($sql, $parameters)));
  }
}

$db = new sqli_protected_db();

$db->query('SELECT name FROM user WHERE id = ?', [$_GET['id']]);
$db->query('SELECT name FROM user WHERE id = ' . $_GET['id']); // TypeError

$db->query('SELECT name FROM user ORDER BY {order}', [], ['order' => $_GET['order']]);
$db->query('SELECT name FROM user ORDER BY ' . $_GET['order']); // TypeError
</code>

<code php>
class query_builder {
  public function where(LiteralString $column, ?LiteralString $operator = null, $value = null) {
    print_r($column . ' ' . $operator . ' ?' . "\n");
  }
}

$qb = new query_builder();

$qb->where('CONCAT(name_first, " ", name_last)', 'LIKE', $_GET['name']);
$qb->where($_GET['field'], '=', $_GET['value']); // TypeError
</code>

===== Considerations =====

==== Performance ====

Máté Kocsis created a [[https://github.com/kocsismate/php-version-benchmarks/|PHP benchmark]] to replicate the old [[https://01.org/node/3774|Intel Tests]]. The results for the implementation found a 0.47% impact with the Symfony demo app, where it did not connect to a database (because the natural variability introduced by a database makes it impossible to measure an impact that small).

==== String Concatenation ====

When two LiteralString values are concatenated, the result is also a LiteralString.

Some people may believe that not supporting concatenation might help debugging, with the thought being, in a long complex script, which only checks if a variable is a LiteralString at the end, it's harder to identify the source of the problem. However, over the last year I've simply not found this to be the case (usual debug techniques work fine), whereas it would be nigh-on-impossible to update every library and all existing code to not use concatenation (e.g. to use a query builder). That said, someone who really wants this strict way of working could use:

<code php>
function literal_implode($separator, $array) {
  $return = implode($separator, $array);
  if (!is_literal_string($return)) {
    throw new Exception('Non-literal-string detected!');
  }
  return $return;
}

function literal_concat(...$a) {
  return literal_implode('', $a);
}
</code>

(On a technical note, we did test an implementation that didn't support concatenation, primarily to see if this would help reduce the performance impact even further. However, the PHP engine can sometimes still concatenate values automatically at compile-time (so concatenation appears to work in some contexts), and it didn't make much (if any) difference in regards to performance, because //concat_function()// in "zend_operators.c" uses //zend_string_extend()// (which needs to remove the //LiteralString// flag) and "zend_vm_def.h" does the same; by supporting a quick concat with an empty string (x2), which would need its flag removed as well).

==== String Splitting ====

In regards to string splitting, we didn't find any realistic use cases, and security features should try to keep the implementation as simple as possible.

Also, the security considerations are different. Concatenation joins known/fixed units together, whereas if you're starting with a LiteralString, and the program allows the Evil-User to split the string (e.g. setting the length in substr), then they get considerable control over the result (it creates an untrusted modification).

While unlikely to be written by a programmer, we can consider these:

<code php>
$length = ($_GET['length'] ?? -5);
$url    = substr('https://example.com/js/a.js?v=55', 0, $length);
$html   = substr('<a href="#">#</a>', 0, $length);
</code>

If $url was used in a Content-Security-Policy, the query string needs to be removed, but as more of the string is removed, the more resources are allowed ("https:" basically allows resources from anywhere). With the HTML example, moving from the tag content to the attribute can be a problem (while HTML Templating Engines should be fine, unfortunately libraries like Twig are not currently context aware, so you need to change from the default 'html' encoding to 'html_attr' encoding).

Krzysztof Kotowicz has confirmed that, at Google, with "go-safe-html", string concatenation is allowed, but splitting is **explicitly** not supported because it "can cause issues"; for example, "arbitrary split position of a HTML string can change the context".

===== Frequently Asked Questions =====

==== FAQ: WHERE IN ====

With SQL, you can use //WHERE id IN (?,?,?)//

User values should be sent to the database separately (with prepared queries), so you should follow the advice from [[https://stackoverflow.com/a/23641033/538216|Levi Morrison]], [[https://www.php.net/manual/en/pdostatement.execute.php#example-1012|PDO Execute]], and [[https://www.drupal.org/docs/7/security/writing-secure-code/database-access#s-multiple-arguments|Drupal Multiple Arguments]], and use something like this:

<code php>
$sql = 'WHERE id IN (' . join(',', array_fill(0, count($ids), '?')) . ')';
</code>

Or, you could use concatenation:

<code php>
$sql = '?';
for ($k = 1; $k < $count; $k++) {
  $sql .= ',?';
}
</code>

Libraries can also abstract this for the developer, e.g. WordPress should support the following in the future ([[https://core.trac.wordpress.org/ticket/54042|#54042]]):

<code php>
$wpdb->prepare('SELECT * FROM table WHERE id IN (%...d)', $ids);
</code>

==== FAQ: Non-Parameterised Values ====

With Table and Field names in SQL, you cannot use parameters, these must be in the SQL string.

Ideally they would be LiteralStrings anyway (so no change needed); and if they are dependent on user input, in most cases you can (and should) use an array of //permitted// LiteralString values:

<code php>
$sort = ($_GET['sort'] ?? NULL);

$fields = [
    'name',
    'email',
    'created',
  ];

$order_id = array_search($sort, $fields);

$sql .= ' ORDER BY ' . $fields[$order_id]; // A LiteralString
</code>

Or, you could use:

<code php>
$fields = [
    'name'    => 'u.full_name',
    'email'   => 'u.email_address',
    'created' => 'DATE(u.created)',
  ];

$sql .= ' ORDER BY ' . ($fields[$sort] ?? 'u.full_name'); // A LiteralString
</code>

There may be some exceptions, see the next section.

==== FAQ: Non-LiteralString Values ====

So what do we do when a non-LiteralString needs to be used?

For example [[https://news-web.php.net/php.internals/87667|Dennis Birkholz]] noted that some Systems/Frameworks define some variables (e.g. table name prefixes) without the use of a LiteralString (e.g. ini/json/yaml). And Larry Garfield noted that in Drupal's ORM "the table name itself is user-defined" (not in the PHP script).

These special non-LiteralString values should still be handled separately (and carefully); where the library checks the sensitive inputs (SQL/HTML/CLI/etc) are still LiteralStrings, and accepts any special values separately, where it can safely/consistently use them (e.g. using backtick escaping for identifiers being sent to a MySQL database).

For example, using a [[https://github.com/craigfrancis/php-is-literal-rfc/blob/main/justification/example.php?ts=4#L194|separate array of $identifiers]]:

<code php>
$sql = "
  SELECT
    t.name,
    t.f1
  FROM
    {my_table} AS t
  WHERE
    t.id = ?"; // A LiteralString

$parameters = [
    $_GET['id'],
  ];

$identifiers = [
    'my_table' => $_GET['table'],
  ];

$results = $db->query($sql, $parameters, $identifiers);
</code>

And WordPress 6.2 is scheduled to support ([[https://core.trac.wordpress.org/ticket/52506|#52506]]):

<code php>
$wpdb->prepare('SELECT * FROM %i', $table_name);
</code>

Or the library could use a [[https://github.com/craigfrancis/php-is-literal-rfc/blob/main/justification/example.php?ts=4#L229|Query Builder]].

==== FAQ: Bypassing It ====

This implementation does not provide an easy way for a developer to mark anything they want as a LiteralString, this is on purpose - we do not want to re-create one of the problems with Taint Checking, by pretending the LiteralString is a flag to say the value is "safe".

Some libraries may want to support their own way to bypass these checks, e.g. a ValueObject:

<code php>
class UnsafeSQL {
  private $value = NULL;
  public function __construct($value) {
    $this->value = $value;
  }
  public function __toString() {
    return $this->value;
  }
}

function example1(LiteralString|UnsafeSQL $input) {
  return $input;
}

function example2($input) {
  if (!is_literal_string($input) && !($input instanceof UnsafeSQL)) {
    error_log('Log issue, but still continue.');
  }
  return $input;
}
</code>

But we do not pretend there aren't ways around this (e.g. using [[https://github.com/craigfrancis/php-is-literal-rfc/blob/main/justification/is-literal-bypass.php|eval]]), but in doing so the developer is clearly choosing to do something wrong. We want to provide safety rails, but there is nothing stopping the developer from intentionally jumping over them.

==== FAQ: Integer Values ====

We wanted to flag integers defined in the source code, in the same way we are doing with strings. Unfortunately [[https://news-web.php.net/php.internals/114964|it would require a big change to add a literal flag on integers]]. Changing how integers work internally would have made a big performance impact, and potentially affected every part of PHP (including extensions).

Due to this limitation, we did consider an approach to trust all integers, where Scott Arciszewski suggested the name //is_noble()//. While this is not as philosophically pure, we continued to explore this possibility because we could not find any way an Injection Vulnerability could be introduced with integers in SQL, HTML, CLI; and other contexts as well (e.g. preg, mail additional_params, XPath query, and even eval). We could not find any character encoding issues either (The closest we could find was EBCDIC, an old IBM character encoding, which encodes the 0-9 characters differently; which anyone using it would need to re-encode either way, and [[https://www.php.net/manual/en/migration80.other-changes.php#migration80.other-changes.ebcdic|EBCDIC is not supported by PHP]]). And we could not find any issue with a 64bit PHP server sending a large number to a 32bit database, because the number is being encoded as characters in a string (so that's also fine). However, the feedback received was that while safe from Injection Vulnerabilities, it becomes a more complex concept, one that might cause developers to assume it is also safe from developer/logic errors. Ultimately the preference was the simpler approach, that did not allow any integers (which is reinforced with the name LiteralString).

==== FAQ: Other Values ====

Like Integers, it would be hard to support Boolean/Float values; they are also a very low-value feature, and we cannot be sure of the security implications.

For example, the value you put in is not always the same as what you get out:

<code php>
var_dump((string) true);  // "1"
var_dump((string) false); // ""
var_dump(2.3 * 100);      // 229.99999999999997

setlocale(LC_ALL, 'de_DE.UTF-8');
var_dump(sprintf('%.3f', 1.23)); // "1,230"
 // Note the comma, which can be bad for SQL.
 // Pre 8.0 this also happened with string casting.
</code>

==== FAQ: Other Functions ====

We made the decision to only support 4 functions that concatenated strings.

There are a lot of other candidates; e.g. adding //strtoupper()// might be reasonable, however we would need to consider the effect of every function and context, making the concept of a LiteralString more complex (e.g. //str_shuffle()// creating unpredictable results, or output varying based on the current locale).

The main request that's come up over the last year is to support //sprintf()//. While this is reasonable for basic concatenation (e.g. only using "%s"), it gets more complicated when coercing values to a different type, or when using formatting. That said, a future RFC might consider changing this (with the main focus being on the implications/risks).

Python has a longer list of [[https://peps.python.org/pep-0675/#appendix-c-str-methods-that-preserve-literalstring|methods that preserve LiteralString]], where they found it tricky to decide what should be allowed, and this created a bit of negative feedback (some people want more functions on the list, while others wish these hadn't been included because it moved away from a simple "developer defined string").

==== FAQ: The Name ====

A "Literal String" is the standard name for strings in source code. See [[https://www.google.com/search?q=what+is+literal+string+in+php|Google]].

> A string literal is the notation for representing a string value within the text of a computer program. In PHP, strings can be created with single quotes, double quotes or using the heredoc or the nowdoc syntax.

LiteralString shows it only accepts strings (not integers, as noted above).

And follows the naming convention of not using underscores for the type/object (e.g. DateTime, DOMDocument, ImageMagick), while using underscores for the //is_literal_string()// function.

It's also the [[https://peps.python.org/pep-0675/#rejected-names|name chosen for the Python implementation]].

==== FAQ: Extensions ====

If an extension is found to be already using the flag we're using for LiteralString (unlikely), that's the same as any new flag being introduced into PHP, and will need to be updated in the same way. And by default, flags are off, which is a fail safe situation.

==== FAQ: Adoption ====

Existing libraries will probably focus on using //is_literal_string()//, as it allows them to easily choose how mistakes are handled, and //function_exists()// makes supporting PHP 8.2 and below very easy.

**WordPress**: After adding support for escaping field/table names (identifiers) with //%i// ([[https://core.trac.wordpress.org/ticket/52506|#52506]]), and to make //IN (?,?,?)// easier with //%...d// ([[https://core.trac.wordpress.org/ticket/54042|#54042]]), a LiteralString check will be added to the //$query// parameter in //wpdb::prepare()//.

**Doctrine**: While not part of the official Doctrine project, the [[https://github.com/phpstan/phpstan-doctrine|phpstan-doctrine]] extension adds experimental support via bleedingEdge (will probably use a separate flag in the future).

**Propel** (Mark Scherer): "given that this would help to more safely work with user input, I think this syntax would really help in Propel." ([[https://github.com/propelorm/Propel2/pull/1788/files|example]]).

**RedBean** (Gabor de Mooij): "You can list RedBeanPHP as a supporter, we will implement this into the core." ([[https://github.com/gabordemooij/redbean/pull/873/files|example]]).

**PhpStorm**: 2022.3 recognises the //literal-string// type ([[https://youtrack.jetbrains.com/issue/WI-64109/literal-string-support-in-phpdoc|WI-64109]]).

**Psalm** (Matthew Brown): 13th June 2021 "I was skeptical about the first draft of this RFC when I saw it last month, but now I see the light (especially with the concat changes)". Then on the 14th June, "I've just added support for a //literal-string// type to Psalm: https://psalm.dev/r/9440908f39" ([[https://github.com/vimeo/psalm/releases/tag/4.8.0|4.8.0]])

**PHPStan** (Ondřej Mirtes): 1st September 2021, has been implemented in [[https://github.com/phpstan/phpstan/releases/tag/0.12.97|0.12.97]].

===== Alternatives =====

==== Static Analysis ====

Both [[https://github.com/vimeo/psalm/releases/tag/4.8.0|Psalm]] and [[https://github.com/phpstan/phpstan/releases/tag/0.12.97|PHPStan]] have supported the //literal-string// type since September 2021.

While I want more developers to use Static Analysis, it's not realistic to expect all PHP developers to always use these tools, and for all PHP code to be updated so Static Analysis can run the strictest checks, and use no baseline (using the JetBrains surveys; in [[https://www.jetbrains.com/lp/devecosystem-2021/php/#PHP_do-you-use-static-analysis|2021]] only 33% used Static Analysis; and [[https://www.jetbrains.com/lp/devecosystem-2022/php/#what-additional-quality-tools-do-you-use-regularly-if-any-|2022]] shows a similar story with 33% (at best) using PHPStan/Psalm/Phan; where the selected developers for both surveys are 3 times more likely to use Laravel than WordPress).

Also, it can be tricky to get current Static Analysis tools to cover every case. For example, they don't currently support [[https://stackoverflow.com/questions/71861442/php-static-analysis-and-recursive-type-checking|recursive type checking]], or [[https://stackoverflow.com/questions/72231302/phpstan-extension-dynamic-return-types-with-value-objects|get a value-object to conditionally return a type]]. In contrast, both are [[https://github.com/craigfrancis/php-is-literal-rfc/blob/main/examples/sql-orm.php#L60|easy with the is_literal_string() function]].

==== Taint Checking ====

Taint Checking incorrectly assumes the output of an escaping function is "safe" for a particular context. While it sounds reasonable in theory, the operation of escaping functions, and the context for which their output is safe, is very hard to define, and leads to a feature that is both complex and unreliable.

<code php>
$sql = 'SELECT * FROM users WHERE id = ' . $db->real_escape_string($id); // INSECURE
$html = "<img src=" . htmlentities($url) . " alt='' />"; // INSECURE
$html = "<a href='" . htmlentities($url) . "'>..."; // INSECURE
</code>

All three examples would be incorrectly considered "safe" (untainted). The first two need the values to be quoted. The third example, //htmlentities()// does not escape single quotes by default before PHP 8.1 ([[https://github.com/php/php-src/commit/50eca61f68815005f3b0f808578cc1ce3b4297f0|fixed]]), and it does not consider the issue of 'javascript:' URLs.

This is why Psalm, which supports Taint Checking, clearly notes these [[https://psalm.dev/docs/security_analysis/#limitations|limitations]].

==== Education ====

Developer training simply does not scale, and mistakes still happen.

We cannot expect everyone to have formal training, know everything from Day 1, and consider programming a full time job. We want new programmers, with a variety of experiences, ages, and backgrounds. Everyone should be guided to do the right thing, and notified as soon as they make a mistake (we all make mistakes). We also need to acknowledge that many programmers are busy, do copy/paste code, don't necessarily understand what it does, edit it for their needs, then simply move on to their next task.

===== Other Programming Languages =====

Similar concepts implemented in other programming languages:

**Python** can use the [[https://github.com/craigfrancis/php-is-literal-rfc/blob/main/others/python/main.py|LiteralString]] type in 3.11 ([[https://eiv.dev/python-pyre/|pyre example]], via [[https://peps.python.org/pep-0675/|PEP 675]]).

**Go** can use an "[[https://github.com/craigfrancis/php-is-literal-rfc/blob/main/others/go/index.go|un-exported string type]]", a technique which is used by [[https://blogtitle.github.io/go-safe-html/|go-safe-html]].

**C++** can use a "[[https://github.com/craigfrancis/php-is-literal-rfc/blob/main/others/cpp/index.cpp|consteval annotation]]".

**Scala** can use "[[https://github.com/craigfrancis/php-is-literal-rfc/tree/main/others/scala|String with Singleton]]".

**Java** can use a "[[https://github.com/craigfrancis/php-is-literal-rfc/blob/main/others/java/src/main/java/com/example/isliteral/index.java|@CompileTimeConstant annotation]]" from [[https://errorprone.info/bugpattern/CompileTimeConstant|Error Prone]] to ensure method parameters can only use "compile-time constant expressions".

**Rust** can use a "[[https://github.com/craigfrancis/php-is-literal-rfc/tree/main/others/rust|procedural macro]]", to check the provided value is a literal at compile-time.

**Node** has the [[https://github.com/craigfrancis/php-is-literal-rfc/blob/main/others/npm/index.js|is-template-object polyfill]], which checks a tag function was provided a "tagged template literal" (this technique is used in [[https://www.npmjs.com/package/safesql|safesql]], via [[https://www.npmjs.com/package/template-tag-common|template-tag-common]]). Alternatively Node developers can use [[https://github.com/craigfrancis/php-is-literal-rfc/blob/main/others/npm-closure-library/index.js|goog.string.Const]] from Google's Closure Library.

**JavaScript** is getting [[https://github.com/tc39/proposal-array-is-template-object|isTemplateObject]], for "Distinguishing strings from a trusted developer from strings that may be attacker controlled" (intended to be [[https://github.com/mikewest/tc39-proposal-literals|used with Trusted Types]]).

**Perl** has a [[https://perldoc.perl.org/perlsec#Taint-mode|Taint Mode]], via the -T flag, where all input is marked as "tainted", and cannot be used by some methods (like commands that modify files), unless you use a regular expression to match and return known-good values (regular expressions are easy to get wrong).

===== History =====

There is a [[https://github.com/laruence/taint|Taint extension for PHP]] by Xinchen Hui, and [[https://wiki.php.net/rfc/taint|a previous RFC proposing it be added to the language]] by Wietse Venema, but Taint Checking is flawed (see notes above).

And there is the [[https://wiki.php.net/rfc/sql_injection_protection|Automatic SQL Injection Protection]] RFC by Matt Tait (this RFC uses a similar concept of the [[https://wiki.php.net/rfc/sql_injection_protection#safeconst|SafeConst]]). When Matt's RFC was being discussed, it was noted:

  * "unfiltered input can affect way more than only SQL" ([[https://news-web.php.net/php.internals/87355|Pierre Joye]]);
  * this amount of work isn't ideal for "just for one use case" ([[https://news-web.php.net/php.internals/87647|Julien Pauli]]);
  * It would have effected every SQL function, such as //mysqli_query()//, //$pdo->query()//, //odbc_exec()//, etc (concerns raised by [[https://news-web.php.net/php.internals/87436|Lester Caine]] and [[https://news-web.php.net/php.internals/87650|Anthony Ferrara]]);
  * Each of those functions would need a bypass for cases where unsafe SQL was intentionally being used (e.g. phpMyAdmin taking SQL from POST data) because some applications intentionally "pass raw, user submitted, SQL" (Ronald Chmara [[https://news-web.php.net/php.internals/87406|1]]/[[https://news-web.php.net/php.internals/87446|2]]).

Last year I wrote the [[https://wiki.php.net/rfc/is_literal|is_literal() RFC]], where the feedback was:

  * "Ideally we would want to assign a variable to be of 'literal' type." [[https://externals.io/message/115306#115308|George P. Banyard]] (covered by this RFC).
  * "There is good progress in taint analysis" [[https://externals.io/message/115306#115455|Marco Pivetta]] (see the flaws noted with Taint Analysis above).
  * "I would like the ecosystem to pick up static analysis more" [[https://externals.io/message/115306#115455|Marco Pivetta]] (I do too, but I doubt we can get everyone using it all the time, at the strictest levels, and no baseline).
  * "the concatenation operation is basically kicking the can down the road [...] using a function like concat_literal() [...] provides immediate feedback" [[https://externals.io/message/115306#115308|George P. Banyard]] and "[literal_concat() makes] it easy to track down issues where they occur" [[https://externals.io/message/115306#115387|Dan Ackroyd]] (I've not found this to be the case, but re-writing all code to not use concatenation is a big change).
  * "I'd prefer proper type or static analysis over adding more functions if that could effectively solve the problem." (this RFC adds the type, and Static Analysis can do this now).
  * "There just too much debate for me to be comfortable and vote yes." (ref the discussions about integer support and concatenation just before the 8.1 deadline).
  * "Bad code should better be fixed through better documentation." (we've tried that, and mistakes still happen).
  * "I think libraries are very unlikely to adopt" (they have, see above).
  * "you can't even trust is_literal() [due to] file_put_contents("data.php", "<?php return $_GET[id];"); $id = require "data.php";" (I doubt any developer will do this by accident).
  * "I don't believe we should expect security or maintainability without (all together): proper education + peer reviewing + static analysis." (these should still happen).
  * "in the real world, you're going to start seeing cases where something is "literal enough", but doesn't pass the is_literal test" (examples were asked for, but no response).

I also agree with [[https://news-web.php.net/php.internals/87400|Scott Arciszewski]], "SQL injection is almost a solved problem [by using] prepared statements", where LiteralString identifies when user input is accidentally included in the SQL string.

On a technical note, the implementation avoids situations that could have confused the developer, by using the Lexer to mark strings as a LiteralString (i.e. earlier in the process):

<code php>
$one = 1;
$a = 'A' . $one; // false, flag removed because it's being concatenated with an integer.
$b = 'A' . 1; // Was true, as the compiler optimised this to the literal 'A1'.

$a = "Hello ";
$b = $a . 2; // Was true, as the 2 was coerced to the string '2' (to optimise the concatenation).

$a = implode("-", [1, 2, 3]); // Was true with OPcache, as it could optimise this to the literal '1-2-3'

$a = chr(97); // Was true, due to the use of Interned Strings.
</code>

===== Backward Incompatible Changes =====

No known BC breaks, except for existing code that contains the userland function //is_literal_string()//, or object //LiteralString//.

===== Proposed PHP Version(s) =====

PHP 8.3

===== RFC Impact =====

==== To SAPIs ====

None known

==== To Existing Extensions ====

None known

==== To Opcache ====

None known

===== Open Issues =====

None

===== Future Scope =====

1) We might re-look at //sprintf()// being able to return a LiteralString.

2) As noted by MarkR, the biggest benefit will come when this flag can be used by PDO and similar functions (//mysqli_query//, //preg_match//, //exec//, etc).

However, first we need libraries to start checking the relevant inputs are a LiteralString. The library can then do their thing, and apply the appropriate escaping, which can result in a value that no longer has the LiteralString flag set, but is perfectly safe for the native functions.

With a future RFC, we could introduce checks for the native functions. For example, if we use the [[https://eiv.dev/trusted-types/|Trusted Types]] concept from JavaScript, the libraries could create a stringable ValueObject as their output. These objects can be added to a list of safe objects for the relevant native functions. The native functions could then **warn** developers when they do not receive a value with the LiteralString flag, or one of the safe objects. These warnings would **not break anything**, they just make developers aware of any mistakes they have made, and we will always need a way of switching them off entirely (e.g. phpMyAdmin).

===== Voting =====

Accept the RFC

<doodle title="LiteralString" auth="craigfrancis" voteType="single" closed="true">
   * Yes
   * No
</doodle>

===== Implementation =====

[[https://github.com/php/php-src/compare/master...krakjoe:literals|Joe Watkin's implementation]] provides //is_literal()//, but will need to be updated to support the LiteralString native type, and re-name the function to //is_literal_string()//.

===== Rejected Features =====

  - [[#faqinteger_values|Supporting Integers]]

===== Thanks =====

  - **Joe Watkins**, krakjoe, for writing the full implementation, including support for concatenation and integers, and helping me though the RFC process.
  - **Máté Kocsis**, mate-kocsis, for setting up and doing the performance testing.
  - **Scott Arciszewski**, CiPHPerCoder, for checking over the original RFC, and provided text on how we could implement integer support under a //is_noble()// name.
  - **Dan Ackroyd**, DanAck, for starting the [[https://github.com/php/php-src/compare/master...Danack:is_literal_attempt_two|first implementation]], which made this a reality, providing //literal_concat()// and //literal_implode()//, and followup on how it should work.
  - **Xinchen Hui**, who created the Taint Extension, allowing me to test the idea; and noting how Taint in PHP5 was complex, but "with PHP7's new zend_string, and string flags, the implementation will become easier" [[https://news-web.php.net/php.internals/87396|source]].
  - **Rowan Francis**, for proof-reading, and helping me make an RFC that contains readable English.
  - **Rowan Tommins**, IMSoP, for helping with the original RFC, focusing on the key features, and put it in context of how it can be used by libraries.
  - **Nikita Popov**, NikiC, for suggesting where the flag could be stored. Initially this was going to be the "GC_PROTECTED flag for strings", which allowed Dan to start the first implementation.
  - **Mark Randall**, MarkR, for suggestions, and noting that "interned strings in PHP have a flag", which started the conversation on how this could be implemented.
  - **Sara Golemon**, SaraMG, for noting that I'd need to explain how //is_literal()// is different to the flawed Taint Checking approach, so we don't get "a false sense of security or require far too much escape hatching".
