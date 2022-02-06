
pub use macro_parser::*;
pub use parser::*;

fn main() {

	let res = parser::parser("Hello %s").unwrap();
	println!("res: {:?}", res);

	let res = macro_parser::parse!("Hello %d", 1);
	println!("res: {:?}", res);

	let res = macro_parser::parse!("Hello %s", "abc".to_string());
	println!("res: {:?}", res);

	let res = parser::Query{ fragment: "Hello ", argument: Argument::String("abc".to_string()) };
	println!("res: {:?}", res);

	// won't compile:
	// "expected string literal"
	// let hello_d = "Hello %d";
	// let res = macro_parser::parse!(123);
	// let res = macro_parser::parse!(hello_c);
	// let res = macro_parser::parse!(hello_d, 1);

	// won't compile:
	// expected `u64`, found `&str`
	//let res = macro_parser::parse!("hello %d", "abc");

}
