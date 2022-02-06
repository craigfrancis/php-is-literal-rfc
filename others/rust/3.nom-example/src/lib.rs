//! This will not compile because the macro is expecting a
//! string literal
//!
//! ```compile_fail
//! let hello_d = "Hello %d";
//! let res = macro_parser::parse!(hello_d, 1);
//!
//! ```
pub use macro_parser::*;
pub use parser::*;

#[cfg(test)]
mod tests {

    #[test]
    fn test() {
        let res = parser::parser("Hello %s").unwrap();
        println!("res: {:?}", res);

        let res = macro_parser::parse!("Hello %d", 1);
        println!("res: {:?}", res);

        let res = macro_parser::parse!("Hello %s", "abc".to_string());
        println!("res: {:?}", res);
        // won't compile:
        // "expected string literal"
        //let res = macro_parser::parse!(123);
        //let res = macro_parser::parse!(hello_c);

        // won't compile:
        // expected `u64`, found `&str`
        //let res = macro_parser::parse!("hello %d", "abc");
    }
}
