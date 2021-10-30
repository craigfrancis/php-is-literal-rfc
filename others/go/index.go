package main

import "bufio"
import "os"
import "fmt"

func main() {

	reader := bufio.NewReader(os.Stdin)
	fmt.Print("Your Name: ")
	your_name, _ := reader.ReadString('\n')
	your_name = your_name[:len(your_name)-1]

	a := StringConstant("<p>Hello ")
	b := StringConstant(your_name)

	// ./index.go:15:21: cannot use your_name (type string) as type stringConstant in argument to StringConstant

	fmt.Printf(a)
	fmt.Printf("\n")
	fmt.Printf(b)
	fmt.Printf("\n")
	fmt.Printf(your_name)
	fmt.Printf("\n\n")

}

type stringConstant string

func StringConstant(v stringConstant) string {
	return string(v)
}
