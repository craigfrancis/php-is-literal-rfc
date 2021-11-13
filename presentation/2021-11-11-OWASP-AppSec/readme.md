# Ending Injection Vulnerabilities

Video: https://youtu.be/06_suQAAfBc

You cannot have an Injection Vulnerability if the string does not include any user data.

---

## Abstract

How programming languages can bring an end to Injection Vulnerabilities, by "distinguishing strings from a trusted developer, from strings that may be attacker controlled".

This simple distinction will allow libraries to ensure Injection Vulnerabilities are not possible, because those sensitive values (e.g. SQL, HTML, CLI strings) cannot contain user values. Instead, it will be up to the well-tested libraries to handle user values; ideally via parameterised queries, but they can also use appropriate escaping.

---

## Outline

Start by showing the typical mistakes that developers make which lead to Injection Vulnerabilities - basic SQL/HTML/CLI string concatenation with user values; but also bad uses of escaping (like '<a href="' . htmlentities($url) . '">' with 'javascript:x' values), and mistakes made when using libraries.

Note how Education and Static Analysis has not (and will not) solve this problem.

Note how programming languages should distinguish between the two types of strings (developer defined, vs everything else); where it is a basic flag based system, similar to Taint Checking (but different, because Taint pretends a value can be flagged as safe when it's been escaped).

Note how this is currently possible in Java and Go, is being used by Google libraries like go-safe-html and go-safesql, and is currently being added to JavaScript.

Show how this could be implemented in PHP (the primary programming language I use).

Note the typical queries people raise: The SQL 'WHERE x IN (?,?,?)', and field names - where they still work with this approach.

Show 2 example libraries, covering SQL and HTML, where the developer cannot introduce an Injection Vulnerability.

---

## Notes to Reviewer

This is presenting a way of coding that's not possible in most languages today, it's to start the discussion on where we should be going, so we can begin pressuring languages to actually address the Injection Vulnerability issue (in a similar way to how Rust addresses memory-safety).

---

## Speaker Bio

Software developer for 20 something years,
OWASP Chapter Co-Lead for Bristol UK