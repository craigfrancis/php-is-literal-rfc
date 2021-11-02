/*

go run index.go

*/

package main

import "bufio"
import "os"
import "fmt"

func main() {

	reader := bufio.NewReader(os.Stdin)
	fmt.Print("Your Name: ")
	your_name, _ := reader.ReadString('\n')
	your_name = your_name[:len(your_name)-1]

	a := OnlyAcceptsStringConstant("<p>Hello ")
	b := OnlyAcceptsStringConstant(your_name)

	// cannot use your_name (type string) as type stringConstant in argument to OnlyAcceptsStringConstant

	fmt.Printf(a)
	fmt.Printf("\n")
	fmt.Printf(b)
	fmt.Printf("\n")
	fmt.Printf(your_name)
	fmt.Printf("\n\n")

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
