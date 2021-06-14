# Function Name

The name of the function has not been decided, the candidates suggested so far include:

---

## `is_literal()`

- It's shorter than `is_from_literal()` (I'm a slow typer).
- Matches the current convention of existing "is_*" functions (all use a single word suffix).

---

## `is_from_literal()`

- Avoid cognitive dissonance with is_literal(), possibly confusing for developers who know what a literal is in C.

---

## `is_lstring()`

- Assuming there's a new literal type, it might be called `lstring`.

---

# Background

During initial conversations, it was noted that strings written by the developer were called literals, so that's how the original name was selected. It also helped that anyone asking [what is a literal string in php](https://www.google.com/search?q=what+is+a+literal+string+in+php) would get a fairly useful answer:

> A string literal is the notation for representing a string value within the text of a computer program. In PHP, strings can be created with single quotes, double quotes or using the heredoc or the nowdoc syntax...

And the name must not imply the string is "safe":

```php
$cli = 'rm -rf ?';
$sql = 'DELETE FROM my_table WHERE my_date >= ?';
eval('$name = "' . $_GET['name'] . '";'); // INSECURE
```

While the first two cannot include Injection Vulnerabilities, the parameters could be set to "/" or "0000-00-00" (providing a nice vanishing magic trick); and the last one, well, they have much bigger issues to worry about (it's clearly irresponsible, and intentionally dangerous).

We must also consider how likely the function name is to clash with any existing userland functions.
