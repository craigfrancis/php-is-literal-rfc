# Example of using nom parsers from a proc macro

This project is organised as 3 crates:
- `nom_macro` is the main project, exposing the proc macro and the generated types
- `parser` holds the nom parsers
- `macro_parser` provides the macro to parse at compile time

We need to separate them because a proc macro project can only export
proc macros functions, no other types. If we want the main project to
expose the parsers or the generated types along with the macro, we need
to have them available in their own crate. So the `parser` crate is
imported by `macro_parser`, and the parsers are reexported by
`nom_macro`. If we do not need to make them available outside of the
proc_macro, they can stay inside the proc macro crate.

The `macro_parser` crate provides a function-like macro that expects
a string literal. If anything other than a string literal is provided
(number, variable, etc), the macro will fail.
The string literal then goes through a nom parser, that extracts a part
of it.
The `quote` crate is then used to generate a structure that contains the
parsed value, and that structure is compiled in place of the macro.
