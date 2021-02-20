# Is Literal Check

This is the Justification for the [is_literal()](https://wiki.php.net/rfc/is_literal) function.

## The Problem

Escaping strings for SQL, HTML, Commands, etc is **very** error prone.

The vast majority of programmers should never do this (mistakes will be made).

Unsafe values (often user supplied) **must** be kept separate (e.g. parameterised SQL), or be processed by something that understands the context (e.g. a HTML Templating Engine).

This is primarily for security reasons, but it can also cause data to be damaged (e.g. ASCII vs UTF-8 issues).

## Example

It's often believed a Database Abstraction (e.g. an ORM) is safe, because you're not writing the SQL yourself.

But take this Doctrine [Query Builder](https://www.doctrine-project.org/projects/doctrine-orm/en/2.7/reference/query-builder.html#high-level-api-methods) example:

```php
$users = $queryBuilder
  ->select('u')
  ->from('User', 'u')
  ->where('u.id = ' . $_GET['id'])
  ->getQuery()
  ->getResult();

// example.php?id=u.id
```

Or this [DQL](https://www.doctrine-project.org/projects/doctrine-orm/en/2.7/reference/dql-doctrine-query-language.html) (Doctrine Query Language) example:

```php
$query = $em->createQuery('SELECT u FROM User u WHERE u.id = ' . $_GET['id']);
```

By mixing strings from a trusted developer (literals), with values that are user (attacker) controlled, we have a problem.

## Common Mistakes

Irrespective of the abstraction in use, there are **many** mistakes that need to be prevented:

```php
echo "<img src=" . $url . " alt='' />";
```

Flawed, and unfortunately a very common XSS issue; e.g. `$url = '/ onerror=alert(1)'`

```php
echo "<img src=" . htmlentities($url) . " alt='' />";
```

Flawed because the attribute value is not quoted; e.g. `$url = '/ onerror=alert(1)'`

```php
echo "<img src='" . htmlentities($url) . "' alt='' />";
```

Flawed because `htmlentities()` doesn't encode single quotes; e.g. `$url = "/' onerror='alert(1)"` (fixed in [PHP 8.2](https://github.com/php/php-src/commit/50eca61f68815005f3b0f808578cc1ce3b4297f0)).

```php
echo '<a href="' . htmlentities($url) . '">Link</a>';
```

Flawed because a link can include JavaScript; e.g. `$url = 'javascript:alert(1)'`

```html
<script>
  var url = "<?= addslashes($url) ?>";
</script>
```

Flawed because `addslashes()` is not HTML context aware; e.g. `$url = '</script><script>alert(1)</script>'`

```php
echo '<a href="/path/?name=' . htmlentities($name) . '">Link</a>';
```

Flawed because `urlencode()` has not been used; e.g. `$name = 'A&B'`

```html
<p><?= htmlentities($url) ?></p>
```

Flawed because the encoding is not guaranteed to be UTF-8 (or ISO-8859-1 before PHP 5.4), so the value could be corrupted.

Also flawed because some browsers (e.g. IE 11), if the charset isn't defined (header or meta tag), could guess the output as UTF-7; e.g. `$url = '+ADw-script+AD4-alert(1)+ADw-+AC8-script+AD4-'`

```php
example.html:
    <img src={{ url }} alt='' />

$loader = new \Twig\Loader\FilesystemLoader('./templates/');
$twig = new \Twig\Environment($loader, ['autoescape' => 'name']);

echo $twig->render('example.html', ['url' => $url]);
```

Flawed because [Twig is not context aware](https://github.com/twigphp/Twig/issues/3394) (in this case, an unquoted HTML attribute); e.g. `$url = '/ onerror=alert(1)'`

```php
$sql = 'SELECT 1 FROM user WHERE name="' . $name . '"';
```

Flawed as it's the classic SQLi vulnerability; e.g. `$name = '" OR ""="'`

```php
$sql = 'SELECT 1 FROM user WHERE id=' . $mysqli->escape_string($id);
```

Flawed because the value has not been quoted; e.g. `$id = 'id'`, or `$id = '0 UNION ...'`

```php
$sql = 'SELECT 1 FROM user WHERE id="' . $mysqli->escape_string($id) . '"';
```

Flawed if 'sql_mode' includes `NO_BACKSLASH_ESCAPES`; e.g. `$id = '2" or "1"="1'`

```php
$sql = 'INSERT INTO user (name) VALUES ("' . $mysqli->escape_string($name) . '")';
```

Flawed if 'SET NAMES latin1' has been used, and escape_string() uses 'utf8'.

```php
$parameters = "-f$email";

// $parameters = '-f' . escapeshellarg($email);

mail('a@example.com', 'Subject', 'Message', NULL, $parameters);
```

Flawed because it's not possible to safely escape values in `$additional_parameters` for `mail()`; e.g. `$email = 'b@example.com -X/www/example.php'`

## The Solution

We need to distinguish between strings from a trusted developer, from those that could be attacker controlled.

This will allow libraries / frameworks to protect against common mistakes; and, in the future, for PHP itself to check for these mistakes.

## Go Implementation

As discussed by [Christoph Kern (Google) in 2015](https://www.usenix.org/conference/usenixsecurity15/symposium-program/presentation/kern) and in [2016](https://www.youtube.com/watch?v=ccfEu-Jj0as), this approach works.

The Go language can do this by checking for "compile time constants", which isn't as good as a run time solution (e.g. the `WHERE IN` issue), but it does work.

Google currently avoids most issues by using [go-safe-html](https://blogtitle.github.io/go-safe-html/) and [safesql](https://github.com/google/go-safeweb/tree/master/safesql).

## JavaScript Implementation

This solution is taking some ideas from TC39, where a similar idea is being discussed for JavaScript, to support the introduction of Trusted Types.

[https://github.com/tc39/proposal-array-is-template-object](https://github.com/tc39/proposal-array-is-template-object)  
[https://github.com/mikewest/tc39-proposal-literals](https://github.com/mikewest/tc39-proposal-literals)

They are looking at "Distinguishing strings from a trusted developer, from strings that may be attacker controlled".

## How it works

Literals are safe values, defined within the PHP scripts, for example:

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

This allows us to ensure commands are a "programmer supplied constant/static/validated string", with all unsafe variables being provided separately (as noted by [Yasuo Ohgaki](https://news-web.php.net/php.internals/87725)).

All systems/frameworks/libraries can then decide if they want to **block** (exception), **educate** (via a notice), or **ignore** these issues (to avoid the "don't nanny" concern raised by [Lester Caine](https://news-web.php.net/php.internals/87383)).

Unlike Taint checking, there must be **no** support for escaping, or equivalent `untaint()` function.

### Solution: SQL Injection, Abstractions

Database abstractions (e.g. ORMs) will be able to ensure they are provided with strings that are safe.

Taking the Doctrine Query Builder mistake above, this check can ensure `->where($predicates)` is a safe literal:

```php
public function where($predicates)
{
    if (function_exists('is_literal') && !is_literal($predicates)) {
        throw new Exception('->where() can only accept a literal');
    }
    ...
}
```

[RedBean](https://redbeanphp.com/index.php?p=/finding) could check `$sql` is a literal:

```php
$users = R::find('user', 'id = ' . $_GET['id']);
```

[PropelORM](http://propelorm.org/Propel/reference/model-criteria.html#relational-api) could check `$clause` is a literal:

```php
$users = UserQuery::create()->where('id = ' . $_GET['id'])->find();
```

The `is_literal()` function could also be used internally by ORM developers, so they can be sure they have created their SQL strings out of literals. This would avoid mistakes such as the ORDER BY issues in the Zend framework [1](https://framework.zend.com/security/advisory/ZF2014-04)/[2](https://framework.zend.com/security/advisory/ZF2016-03).

### Solution: SQL Injection, Basic

A simple example:

```php
$sql = 'SELECT * FROM table WHERE id = ?';

$result = $db->exec($sql, [$id]);
```

Checked in this simple database abstraction with:

```php
class db {

  public function exec($sql, $parameters = []) {

    if (!is_literal($sql)) {
      throw new Exception('SQL must be a literal.');
    }

    $statement = $this->pdo->prepare($sql);
    $statement->execute($parameters);
    return $statement->fetchAll();

  }

}
```

And maybe PDO, mysqli, etc could check the SQL string in the future.

### Solution: SQL Injection, ORDER BY

To ensure `ORDER BY` can be set via the user, but only with acceptable values:

```php
$order_fields = [
    'name',
    'created',
    'admin',
  ];

$order_id = array_search(($_GET['sort'] ?? NULL), $order_fields);

$sql = ' ORDER BY ' . $order_fields[$order_id];
```

### Solution: SQL Injection, WHERE IN

Most SQL strings can be a simple concatenations of literal values, but `WHERE x IN (?,?,?)` needs to use a variable number of literal placeholders.

There needs to be a special case just for `array_fill()`+`implode()`, so the `is_literal` state can be preserved, allowing us to create the safe literal string '?,?,?':

```php
$in_sql = implode(',', array_fill(0, count($ids), '?'));

$sql = 'SELECT * FROM table WHERE id IN (' . $in_sql . ')';
```

### Solution: CLI Injection

Rather than using functions such as:

* `exec()`
* `shell_exec()`
* `system()`
* `passthru()`

Frameworks (or PHP) could introduce something similar to `pcntl_exec()`, where arguments are provided separately.

Maybe a [parameterised_exec()](./examples/cli-basic.php) function, which takes a safe literal for the command, and use parameters for the arguments:

```php
$output = parameterised_exec('grep ? /path/to/file | wc -l', [
    'example',
  ]);
```

### Solution: HTML Injection

Template engines should receive variables separately from the raw HTML.

Often the engine will get the HTML from static files (safe):

```php
$html = file_get_contents('/path/to/template.html');
```

But small snippets of HTML are often easier to define as a literal within the PHP script:

```php
$url = url('/example/path/', ['name' => $name]);

echo ht('<a href="?">?</a>', [$url, $name]);
```

In this example the [ht()](./examples/html-snippets/html-template.php) function takes the HTML literal as the first argument, parameterised values second, and understands that it must accept a safe [URL object](./examples/html-snippets/url.php) for the link (not 'javascript:').

Or, how about this [template_xpath()](./examples/html-template-xpath.php) example:

```php
$template_html = '
  <p>Hello <span id="username"></span></p>
  <p><a>Website</a></p>';

echo template_xpath($template_html, [
    '//span[@id="username"]' => [
        NULL      => 'Name', // The textContent
        'class'   => 'admin',
        'data-id' => '123',
      ],
    '//a' => [
        'href' => 'https://example.com',
      ],
  ]);
```
