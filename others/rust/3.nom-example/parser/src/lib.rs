use nom::{
    bytes::complete::{tag, take_while1},
    character::complete::one_of,
    IResult,
};

#[derive(Debug)]
pub struct Query<'a> {
    pub fragment: &'a str,
    pub argument: Argument,
}

#[derive(Debug)]
pub enum Argument {
    Number(u64),
    String(String),
}

pub fn parser(i: &str) -> IResult<&str, (&str, char)> {
    let (i, fragment) = take_while1(|c| c != '%')(i)?;
    let (i, _) = tag("%")(i)?;
    let (i, c) = one_of("sd")(i)?;

    Ok((i, (fragment, c)))
}
