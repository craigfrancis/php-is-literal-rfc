
// Procedural macros must be defined in their own crate.
// So a library (e.g. Database abstraction, HTML templating, etc)
// would need to keep this separate (it needs to be  compiled first).

extern crate proc_macro;

use proc_macro::TokenStream;

// A proc macro takes a TokenStream as its argument, and returns a TokenStream.
#[proc_macro]
pub fn html_add(input: TokenStream) -> TokenStream {

	// Use `syn` @ https://docs.rs/syn/
	// Where `syn::parse()` returns a single Result from the `input` TokenStream.
	// A Result contains something, or an Error.
	// Using `unwrap()` will either return that thing, or panic.
	// And because `value` is defined as a `syn::LitStr` struct,
	// this will Error("expected string literal") if it does not match.

	let value: syn::LitStr = syn::parse(input).unwrap();

	// Use `quote` @ https://docs.rs/quote/
	// Turn the Rust "syntax tree data structure" (in this case, simply `#value`)/
	// And using `into()` we can return a TokenStream.
	// This basically returns code to replace the macro call.

	quote::quote!{ example_library_backend::html_add_unsafe( #value ) }.into()

}
