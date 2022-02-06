
pub use example_library::*;

fn main() {

	let response = is_literal_string!("Example Literal");
	println!("Response: {:?}", response);

	// Won't compile: "expected string literal"
	//
	// let my_string = "Hello";
	// let response = is_literal_string!(my_string);
	// let response = is_literal_string!(123);

}
