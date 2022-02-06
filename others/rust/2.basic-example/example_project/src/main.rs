
use example_library_macro::*;

fn main() {

// Can I have just one crate (library), as a single dependency, where it provides both the proc-macro and an example-function?
// Can the proc macro call a private function (from the library)? e.g. the code here cannot directly call `html_add_unsafe()`

	//--------------------------------------------------
	// Accepts a literal string

		html_add!("<p>Hello <span>?</span></p>");

	//--------------------------------------------------
	// Won't compile: "expected string literal"

		// let my_string = "<p>Hello</p>";
		// html_add!(my_string);
		// html_add!(123);

	//--------------------------------------------------
	// Example of bad user value concatenation

		let user_value = std::env::args().nth(1).unwrap_or("MyName".to_string());

		let mut unsafe_html: String = "".to_owned();
		unsafe_html.push_str("<p>Hi ");
		unsafe_html.push_str(&user_value);
		unsafe_html.push_str("</p>");

		println!("unsafe_html = {:?}", unsafe_html);

		// html_add!(unsafe_html);

		example_library_backend::html_add_unsafe(&unsafe_html);

}
