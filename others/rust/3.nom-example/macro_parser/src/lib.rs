extern crate proc_macro;
use proc_macro::TokenStream;
use quote::quote;
use syn::{
    LitStr, Expr, Token,
    parse::{Result, ParseStream, Parse}
};

/// a proc macro takes tokens as argument, and generates tokens
#[proc_macro]
pub fn parse(input: TokenStream) -> TokenStream {
    let ParsedQuery { query, argument } = syn::parse(input).unwrap();

    // the parser extracted the format string argument and its type,
    // so we can generate code using the right type, and the compiler
    // will check it
    let quote_argument = match argument {
        ParsedArgument::Number(e) => quote! {
            parser::Argument::Number(#e)
        },
        ParsedArgument::String(e) => quote! {
            parser::Argument::String(#e)
        },
    };

    // we can then generate code using what we parsed. That
    // code will replace the macro call
    let gen = quote! {
        parser::Query {
            fragment: #query,
            argument: #quote_argument,
        }
    };
    gen.into()
}

struct ParsedQuery {
    query: String,
    argument: ParsedArgument,
}

enum ParsedArgument {
    Number(Expr),
    String(Expr),
}

impl Parse for ParsedQuery {
    fn parse(input: ParseStream) -> Result<Self> {
        // we expect a string literal here, we let syn extract it
        let query: LitStr = input.parse()?;
        let string = query.value();

        // we can then parse that string. We unwrap here because
        // panicking will display a compilation error
        let (_, (fragment, argument_type)) = parser::parser(&string).unwrap();

        let _: Token![,] = input.parse()?;

        let expr: Expr = input.parse()?;

        if !input.is_empty() {
            return Err(syn::parse::Error::new(input.span(), "unpexected token"));
        }

        let argument = match argument_type {
            'd' => ParsedArgument::Number(expr),
            's' => ParsedArgument::String(expr),
            // already checked by the parser
            _ => unreachable!(),
        };

        Ok(ParsedQuery {
            query: fragment.to_string(), argument
        })
    }
}
