
// <dependency>
//     <groupId>org.apache.commons</groupId>
//     <artifactId>commons-text</artifactId>
//     <version>1.8</version>
// </dependency>
// <dependency>
//     <groupId>org.springframework</groupId>
//     <artifactId>spring-web</artifactId>
//     <version>5.3.13</version>
// </dependency>
// <dependency>
//     <groupId>com.google.guava</groupId>
//     <artifactId>guava</artifactId>
//     <version>30.1.1-jre</version>
// </dependency>

// ./maven/bin/mvn package
// ./maven/bin/mvn exec:java -Dexec.mainClass="index"

import org.apache.commons.text.StringEscapeUtils;
import org.springframework.web.util.HtmlUtils;
import com.google.common.html.HtmlEscapers;

public class index {

	public static void main(String[] args) {

		System.out.println(StringEscapeUtils.escapeHtml4("Apache < '")); // For HTML 4.0
		System.out.println(HtmlUtils.htmlEscape("Spring < '"));
		System.out.println(HtmlEscapers.htmlEscaper().escape("Google < '"));

		// Apache &lt; '
		// Spring &lt; &#39;
		// Google &lt; &#39;

	}

}
