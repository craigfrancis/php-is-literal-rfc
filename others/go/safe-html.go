/*

go mod init example/isliteral
go mod tidy

go run safe-html.go

*/

package main

import "bufio"
import "os"
import "fmt"

import "github.com/google/safehtml"
import "github.com/google/safehtml/template"

func main() {

	reader := bufio.NewReader(os.Stdin)
	fmt.Print("Your Name: ")
	your_name, _ := reader.ReadString('\n')
	your_name = your_name[:len(your_name)-1]

	//--------------------------------------------------

	type my_javascript_data struct {
		Name string
	}

	data := my_javascript_data{
		Name: your_name,
	}

	const script = "alert('Hi ' + msg['Name'] + '!');"

	alert_script, err := safehtml.ScriptFromDataAndConstant("msg", data, script)
	if err != nil {
		fmt.Printf("while building script from data")
	}

	fmt.Printf("\n\n<script>\n")
	fmt.Printf(alert_script.String())
	fmt.Printf("\n</script>\n\n")

	//--------------------------------------------------

	// a := safehtml.ScriptFromConstant("var my_script = 'example';")
	// b := safehtml.ScriptFromConstant(your_name)

	// cannot use your_name (type string) as type safehtml.stringConstant in argument to safehtml.ScriptFromConstant

	//--------------------------------------------------

	a := template.MustParseAndExecuteToHTML("<p>Hello ")
	b := safehtml.HTMLEscaped(your_name)
	c := template.MustParseAndExecuteToHTML("</p>")

	para_1 := safehtml.HTMLConcat(a, b, c)

	fmt.Printf(para_1.String())
	fmt.Printf("\n\n")

	//--------------------------------------------------

	// para_2 := safehtml.HTMLConcat(a, b, c, your_name)

	// cannot use your_name (type string) as type safehtml.HTML in argument to safehtml.HTMLConcat

	//--------------------------------------------------

	// body_html := "<p>Test</p>"
	//
	// t, _ := template.New("").Parse(`<html>` + body_html + `</html>`)

	// cannot use "<html>" + body_html + "</html>" (type string) as type

	//--------------------------------------------------

	t, _ := template.New("Name").Parse(`
		<html>
			<head>
				<script>
					{{ .JS }}
				</script>
			</head>
			<body>
				<p>Hello {{ .Name }}</p>
				<p><a href="{{ .Link }}">My Link</a></p>
			</body>
		</html>`)

	type my_template_data struct {
		JS   safehtml.Script // Inline JS should still be avoided (for a simple CSP)
		Name string
		Link string
	}

	//--------------------------------------------------

	data_1 := my_template_data{
		JS:   alert_script,
		Name: your_name,
		Link: "https://example.com",
	}

	h1, err := t.ExecuteToHTML(data_1)

	fmt.Printf(h1.String())
	fmt.Printf("\n\n")

	//--------------------------------------------------

	// data_2 := my_template_data{
	// 	JS:   your_name,
	// 	Name: your_name,
	// 	Link: "javascript:alert()",
	// }
	//
	// html_2, err := t.ExecuteToHTML(data_2)
	//
	// fmt.Printf(html_2.String())
	// fmt.Printf("\n\n")

	// cannot use your_name (type string) as type safehtml.Script in field value

	// <a href="about:invalid#zGoSafez">

	//--------------------------------------------------

}
