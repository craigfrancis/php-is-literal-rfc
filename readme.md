# PHP RFC: Is Literal

* Version: 0.1
* Date: 2020-03-21
* Author: Craig Francis, craig#at#craigfrancis.co.uk
* Status: Draft
* First Published at: https://wiki.php.net/rfc/is_literal

## Introduction

Add an `is_literal()` function, so developers/frameworks can be sure they are working with a safe value - one created from one or more literals, defined within PHP scripts.

This function would allows developers/frameworks, at runtime, to warn or block SQL Injection, Command Line Injection, and many cases of HTML Injection.

Commands can then be tested to ensure they are a "programmer supplied constant/static/validated string", and all other unsafe variables are provided separately (as noted by [Yasuo Ohgaki](https://news-web.php.net/php.internals/87725)).

This will also allow systems/frameworks to decide if they want to **block**, **educate** (via a notice), or **ignore** these issues (to avoid the "don't nanny" concern raised by [Lester Caine](https://news-web.php.net/php.internals/87383)).

Literals are values defined within the PHP scripts, for example:

    $a = 'Hi';
    $b = 'Example ' . $a;
    is_literal($b); // Returns true
    
    $c = 'Example ' . $_GET['id'];
    is_literal($c); // Returns false

## Related JavaScript Implementation

This proposal is taking some ideas from TC39, where a similar idea is being discussed for JavaScript, to support the introduction of Trusted Types.

[https://github.com/tc39/proposal-array-is-template-object](https://github.com/tc39/proposal-array-is-template-object)  
[https://github.com/mikewest/tc39-proposal-literals](https://github.com/mikewest/tc39-proposal-literals)

They are looking at "Distinguishing strings from a trusted developer, from strings that may be attacker controlled".

## Taint Checking

Xinchen Hui has done some amazing work with the Taint extension:

[https://github.com/laruence/taint](https://github.com/laruence/taint)

Unfortunately this approach does not address all issues, mainly because it still allows string escaping, which is only "[Theoretically Safe](https://www.php.net/manual/en/pdo.quote.php)" (typically due to character encoding issues), nor does it address issues such as missing quotes:

    $sql = 'DELETE FROM table WHERE id = ' . mysqli_real_escape_string($db, $_GET['id']);
    
    // delete.php?id=id
    
    // DELETE FROM table WHERE id = id
  
    $html = '<img src=' . htmlentities($_GET['url']) . ' />';
    
    // example.php?url=x%20onerror=alert(cookie)
    
    // <img src=x onerror=alert(cookie) />

The Taint extension also [conflicts with XDebug](https://github.com/laruence/taint/blob/4a6c4cb2613e27f5604d2021802c144a954caff8/taint.c#L63) (sorry Derick),

## Previous RFC

Matt Tait suggested [[https://wiki.php.net/rfc/sql_injection_protection||Automatic SQL Injection Protection]].

It was noted that "unfiltered input can affect way more than only SQL" ([Pierre Joye](https://news-web.php.net/php.internals/87355)), and this amount of work isn't ideal for "just for one use case" ([Julien Pauli](https://news-web.php.net/php.internals/87647)).

Where it would have effected every SQL function, such as `mysqli_query()`, `$pdo->query()`, `odbc_exec()`, etc (concerns raised by [Lester Caine](https://news-web.php.net/php.internals/87436) and [Anthony Ferrara](https://news-web.php.net/php.internals/87650)).

And each of those functions would need a bypass for cases where unsafe SQL was intentionally being used (e.g. phpMyAdmin taking SQL from POST data) because some applications intentionally "pass raw, user submitted, SQL" (Ronald Chmara [1](https://news-web.php.net/php.internals/87406)/[2](https://news-web.php.net/php.internals/87446)).

I also agree that "SQL injection is almost a solved problem [by using] prepared statements" ([Scott Arciszewski](https://news-web.php.net/php.internals/87400)), but we do need something that can identify mistakes, ideally at runtime.

## Proposal

Add an `is_literal()` function to check if a given variable has only been created by Literal(s).

This uses a similar definition as the [SafeConst](https://wiki.php.net/rfc/sql_injection_protection#safeconst) by Matt Tait, but it does not need to accept Integer or FloatingPoint variables as safe (unless it makes the implementation easier), nor should it effect any existing functions.

Thanks to [Xinchen Hui](https://news-web.php.net/php.internals/87396), we know the PHP5 Taint extension was complex, but "with PHP7's new zend_string, and string flags, the implementation will become easier".

And thanks to [Mark R](https://chat.stackoverflow.com/transcript/message/48927813#48927813), it might be possible to use the fact that "interned strings in PHP have a flag", which is there because these "can't be freed".

Unlike the Taint extension, there is no need to provide an equivalent `untaint()` function.

## Examples

### SQL Injection, Basic

A simple example:

    $sql = 'SELECT * FROM table WHERE id = ?';
    
    $result = $db->exec($sql, [$id]);

Checked in the framework by:

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

It will also work with string concatenation:

    define('TABLE', 'example');
    
    $sql = 'SELECT * FROM ' . TABLE . ' WHERE id = ?';
    
      is_literal($sql); // Returns true
    
    $sql .= ' AND id = ' . mysqli_real_escape_string($db, $_GET['id']);
    
      is_literal($sql); // Returns false

### SQL Injection, ORDER BY

To ensure `ORDER BY` can be set via the user, but only use acceptable values:

    $order_fields = [
        'name',
        'created',
        'admin',
      ];
    
    $order_id = array_search(($_GET['sort'] ?? NULL), $order_fields);
    
    $sql = ' ORDER BY ' . $order_fields[$order_id];

### SQL Injection, WHERE IN

Most SQL strings can be a concatenations of literal values, but `WHERE x IN (?,?,?)` needs to use a variable number of literal placeholders.

So there `might` need to be a special case for `array_fill()`+`implode()` or `str_repeat()`+`substr()` to create something like '?,?,?'

    $in_sql = implode(',', array_fill(0, count($ids), '?'));
    
    // or
    
    $in_sql = substr(str_repeat('?,', count($ids)), 0, -1);

To be used with:

    $sql = 'SELECT * FROM table WHERE id IN (' . $in_sql . ')';

### SQL Injection, ORM Usage

[Doctrine](https://www.doctrine-project.org/projects/doctrine-orm/en/2.7/reference/query-builder.html#high-level-api-methods) could use this to ensure `$predicates` is a literal:

    $users = $queryBuilder
      ->select('u')
      ->from('User', 'u')
      ->where('u.id = ' . $_GET['id'])
      ->getQuery()
      ->getResult();
    
    // example.php?id=u.id

Where this mistake could be identified by:

    public function where($predicates)
    {
        if (!is_literal($predicates)) {
            throw new Exception('Can only accept a literal');
        }
        ...
    }

[RedBean](https://redbeanphp.com/index.php?p=/finding) could check `$sql` is a literal:

    $users = R::find('user', 'id = ' . $_GET['id']);

[PropelORM](http://propelorm.org/Propel/reference/model-criteria.html#relational-api) could check `$clause` is a literal:

    $users = UserQuery::create()->where('id = ' . $_GET['id'])->find();

### SQL Injection, ORM Internal

The `is_literal()` function could be used by ORM developers, so they can be sure they have created an SQL string out of literals.

This would avoid mistakes such as the ORDER BY issues in the Zend framework [1](https://framework.zend.com/security/advisory/ZF2014-04)/[2](https://framework.zend.com/security/advisory/ZF2016-03).

### CLI Injection

Rather than using functions such as:

* `exec()`
* `shell_exec()`
* `system()`
* `passthru()`

Frameworks (or PHP) could introduce something similar to `pcntl_exec()`, where arguments are provided separately.

Or, take a verified literal for the command, and use parameters for the arguments (like SQL):

    $output = parameterised_exec('grep ? /path/to/file | wc -l', [
        'example',
      ]);

Rough implementation:

    function parameterised_exec($cmd, $args = []) {
    
      if (!is_literal($cmd)) {
        throw new Exception('The first argument must be a literal');
      }
    
      $offset = 0;
      $k = 0;
      while (($pos = strpos($cmd, '?', $offset)) !== false) {
        if (!isset($args[$k])) {
          throw new Exception('Missing parameter "' . ($k + 1) . '"');
          exit();
        }
        $arg = escapeshellarg($args[$k]);
        $cmd = substr($cmd, 0, $pos) . $arg . substr($cmd, ($pos + 1));
        $offset = ($pos + strlen($arg));
        $k++;
      }
      if (isset($args[$k])) {
        throw new Exception('Unused parameter "' . ($k + 1) . '"');
        exit();
      }
    
      return exec($cmd);
    
    }

### HTML Injection

Template engines should receive variables separately from the raw HTML.

Often the engine will get the HTML from static files:

    $html = file_get_contents('/path/to/template.html');

But small snippets of HTML are often easier to define as a literal within the PHP script:

    $template_html = '
      <p>Hello <span id="username"></span></p>
      <p><a>Website</a></p>';

Where the variables are supplied separately, in this example I'm using XPaths:

    $values = [
        '//span[@id="username"]' => [
            NULL      => 'Name', // The textContent
            'class'   => 'admin',
            'data-id' => '123',
          ],
        '//a' => [
            'href' => 'https://example.com',
          ],
      ];
    
    echo template_parse($template_html, $values);

Being sure the HTML does not contain unsafe variables, the templating engine can accept and apply the supplied variables for the relevant context, for example:

    function template_parse($html, $values) {
    
      if (!is_literal($html)) {
        throw new Exception('Invalid Template HTML.');
      }
    
      $dom = new DomDocument();
      $dom->loadHTML('<?xml encoding="UTF-8">' . $html);
    
      $xpath = new DOMXPath($dom);
    
      foreach ($values as $query => $attributes) {
    
        if (!is_literal($query)) {
          throw new Exception('Invalid Template XPath.');
        }
    
        foreach ($xpath->query($query) as $element) {
          foreach ($attributes as $attribute => $value) {
    
            if (!is_literal($attribute)) {
              throw new Exception('Invalid Template Attribute.');
            }
    
            if ($attribute) {
              $safe = false;
              if ($attribute == 'href') {
                if (preg_match('/^https?:\/\//', $value)) {
                  $safe = true; // Not "javascript:..."
                }
              } else if ($attribute == 'class') {
                if (in_array($value, ['admin', 'important'])) {
                  $safe = true; // Only allow specific classes?
                }
              } else if (preg_match('/^data-[a-z]+$/', $attribute)) {
                if (preg_match('/^[a-z0-9 ]+$/i', $value)) {
                  $safe = true;
                }
              }
              if ($safe) {
                $element->setAttribute($attribute, $value);
              }
            } else {
              $element->textContent = $value;
            }
    
          }
        }
    
      }
    
      $html = '';
      $body = $dom->documentElement->firstChild;
      if ($body->hasChildNodes()) {
        foreach ($body->childNodes as $node) {
          $html .= $dom->saveXML($node);
        }
      }
    
      return $html;
    
    }

## Backward Incompatible Changes

Not sure

## Proposed PHP Version(s)

PHP 8?

## RFC Impact

### To SAPIs

Not sure

### To Existing Extensions

Not sure

### To Opcache

Not sure

## Open Issues

1. Would this cause performance issues?
2. Can `array_fill()`+`implode()` or `str_repeat()`+`substr()` pass though the "is_literal" flag for the "WHERE IN" case?
3. Should the function be named `is_from_literal()`? (suggestion from [Jakob Givoni](https://news-web.php.net/php.internals/109197))
4. Systems/Frameworks that define certain variables (e.g. table name prefixes) without the use of a literal (e.g. ini/json/yaml files), won't be able to use this check, as originally noted by [Dennis Birkholz](https://news-web.php.net/php.internals/87667).

## Alternatives

1. The current Taint Extension (notes above)
2. Using static analysis (not runtime), for example [psalm](https://psalm.dev/) (thanks [Tyson Andre](https://news-web.php.net/php.internals/109192))

## Unaffected PHP Functionality

Not sure

## Future

Certain functions (`mysqli_query`, `preg_match`, etc) might use this information to generate a error/warning/notice.

## Proposed Voting Choices

Not sure

## Patches and Tests

A volunteer is needed to help with implementation.

## Implementation

N/A

## References

[https://wiki.php.net/rfc/sql_injection_protection](https://wiki.php.net/rfc/sql_injection_protection)

## Rejected Features

N/A
