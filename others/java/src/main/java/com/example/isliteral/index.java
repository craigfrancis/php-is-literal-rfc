
import java.util.Scanner;
import com.google.errorprone.annotations.CompileTimeConstant;

public class index {

	private static void print_constant(@CompileTimeConstant String value) {
		System.out.println(value);
	}

	public static void main(String[] args) {

		System.out.print("Your Name: ");
		Scanner scanner = new Scanner(System.in);
		String your_name = scanner.next();
		scanner.close();

		System.out.println("Your Name is: " + your_name);

		print_constant("Compile Time Constant");

		// print_constant("Your Name is: " + your_name);

		// error: [CompileTimeConstant] Non-compile-time constant expression passed to parameter with @CompileTimeConstant type annotation.

	}

}
