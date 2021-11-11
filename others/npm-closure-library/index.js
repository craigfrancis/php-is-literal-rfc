
require("google-closure-library");

goog.require("goog.string.Const");

console.log("Your Name: ");

process.stdin.once('data', (chunk) => {

	var your_name = chunk.toString();

	var a = goog.string.Const.from("<p>Hello, ");

	var b = goog.string.Const.from(your_name);

	console.log(a.toString());
	console.log(b.toString());

});



