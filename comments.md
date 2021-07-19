## Comments

Results: 10 votes in favour, and 23 against.

Only 4 of 23 'no' voters provided any comment as to why they voted that way on the mailing list, which I feel undermines the point of the Request For Comment process, with an additional 5 responding personally off-list after prompting. This makes it harder (or impossible) for points to be discussed and addressed.

These comments be summarised as:

- Wanting a different implementation (e.g. no string concatenation support).
- Should be addressed via Static Analysis.
- Should be addressed via better documentation.

If this was implemented as a dedicated type, it might address some of the feedback - on the basis that it would be useful to Static Analysis, and help with debugging.

Since this RFC has started, the ['literal-string' type has been added to Psalm](https://github.com/vimeo/psalm/releases/tag/4.8.0).

---

For the details of those 9 responses:

- "[By using literal_concat() for concatenation, it makes] it easy to track down issues where they occur" [Dan Ackroyd](https://externals.io/message/115306#115387).
- "Although I think the idea of the feature is useful, I'm not so sure about the implementation. [...] Ideally we would want to assign a variable to be of 'literal' type. [...] using a function like concat_literal() which checks that the inputs are indeed literals provides immediate feedback." [George P. Banyard](https://externals.io/message/115306#115308).
- "There just too much debate for me to be comfortable and vote yes."
- "Bad code should better be fixed through better documentation."
- "I'd prefer proper type or static analysis over adding more functions if that could effectively solve the problem."
- "I think libraries are very unlikely to adopt, [...] hard to track down where a string started being "not literal", [...] you can't even trust is_literal() [due to] `file_put_contents("data.php", "<?php return $_GET[id];"); $id = require "data.php";`. [...] I don't believe we should expect security or maintainability without (all together): proper education + peer reviewing + static analysis."
- "The false sense of security is still present, [... and] in the real world, you're going to start seeing cases where something is "literal enough", but doesn't pass the is_literal test" (examples were asked for, but no response).
- "This is not a runtime problem, [...] There is good progress in taint analysis, [...] I would like the ecosystem to pick up static analysis more" [Marco Pivetta](https://externals.io/message/115306#115455).
- "I voted "no" for reasons similar to those explained by Marco."

It's also worth noting that 2 no votes were dropped after concerns about performance were addressed; in particular "While I'm not really happy with the technical implications, your numbers clearly show that there is barely any impact in practice" [Nikita Popov](https://news-web.php.net/php.internals/115435).
