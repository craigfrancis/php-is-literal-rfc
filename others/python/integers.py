#!/usr/bin/env python3.11

import sys
import typing

	# Test at:
	# https://pyre-check.org/play/

def main() -> int:

  salutation = "Hello";

  only_literal_string("<p>" + salutation + " ") # String concatenation works.

  only_literal_string(123) # ERROR: Incompatible parameter type [6]: In call `only_literal_string`, for 1st positional argument, expected `typing_extensions.LiteralString` but got `typing_extensions.Literal[123]`.

  # only_literal_string("A" + 1) # ERROR: can only concatenate str (not "int") to str

  one = 1;
  # only_literal_string("A" + one) # ERROR: can only concatenate str (not "int") to str

  two = "2";
  only_literal_string("B" + two) # Works

  return 0

def only_literal_string(my_string: typing.LiteralString) -> None:
  print(my_string)

if __name__ == '__main__':
  sys.exit(main())
