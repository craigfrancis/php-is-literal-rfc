/*

go run integers.go

*/

package main

import "fmt"

func main() {

	const salutation = "Hello"

	a := OnlyAcceptsStringConstant("<p>" + salutation + " ") // String concatenation works.

	b := OnlyAcceptsStringConstant(1) // ERROR: cannot use 1 (untyped int constant) as stringConstant value in argument to OnlyAcceptsStringConstant

	c := OnlyAcceptsStringConstant("A" + 1) // ERROR: invalid operation: "A" + 1 (mismatched types untyped string and untyped int)

	fmt.Printf(a)
	fmt.Printf("\n")
	fmt.Printf(b)
	fmt.Printf("\n")
	fmt.Printf(c)
	fmt.Printf("\n")

}

// A name is *exported* if it begins with a capital letter.
//
// stringConstant is an unexported string type. Users of this package cannot
// create values of this type except by passing an untyped string constant to
// functions which expect a stringConstant. This type should only be used in
// function and method parameters.
type stringConstant string

func OnlyAcceptsStringConstant(v stringConstant) string {
	return string(v)
}
