<?php

	// 1) We are protecting against Injection Vulnerabilities.
	//    We cannot protect against every kind of issue, e.g.
	//
	//      $sql = 'DELETE FROM my_table WHERE my_date >= ?';
	//
	//      $parameters = [$_GET['date']]; // '0000-00-00' might be an issue.
	//
	//      https://wiki.php.net/rfc/is_literal#limitations
	//
	// 2) We cannot protect against developers who are clearly
	//    trying to bypass these checks, e.g. using eval & var_export

//--------------------------------------------------
// The library

	class html_template {

		//--------------------------------------------------
		// Common

			protected $protection_level = 1;
				// 0 = No checks, could be useful on the production server.
				// 1 = Just warnings, the default.
				// 2 = Exceptions, for anyone who wants to be absolutely sure.

			function literal_check($var) {
				if (!function_exists('is_literal') || is_literal($var)) {
					// Fine - This is a programmer defined string (bingo), or not using PHP 8.1
				} else if ($var instanceof unsafe_value) {
					// Fine - Not ideal, but at least they know this one is unsafe.
				} else if ($this->protection_level === 0) {
					// Fine - Programmer aware, and is choosing to disable this check everywhere.
				} else if ($this->protection_level === 1) {
					trigger_error('Non-literal value detected!', E_USER_WARNING);
				} else {
					throw new Exception('Non-literal value detected!');
				}
			}
			function enforce_injection_protection() {
				$this->protection_level = 2;
			}
			function unsafe_disable_injection_protection() {
				$this->protection_level = 0; // Not recommended, try `new unsafe_value('XXX')` for special cases.
			}

		//--------------------------------------------------
		// Variables

			protected $template_html = [];
			protected $template_end = NULL;
			protected $template_parameters = NULL;
			protected $template_parameter_types = [];
			protected $template_allowed = [ // Do not allow <script>, <style>, <link>, <object>, <embed> tags; or attributes that can include JS (e.g. style, onload, dynsrc)... although some can accept url(x) values

					'div'        => ['id' => 'ref', 'class' => 'ref', 'role' => 'text'],
					'span'       => ['id' => 'ref', 'class' => 'ref', 'role' => 'text'],
					'h1'         => ['id' => 'ref', 'class' => 'ref'],
					'h2'         => ['id' => 'ref', 'class' => 'ref'],
					'h3'         => ['id' => 'ref', 'class' => 'ref'],
					'h4'         => ['id' => 'ref', 'class' => 'ref'],
					'h5'         => ['id' => 'ref', 'class' => 'ref'],
					'h6'         => ['id' => 'ref', 'class' => 'ref'],
					'p'          => ['id' => 'ref', 'class' => 'ref'],
					'ul'         => ['id' => 'ref', 'class' => 'ref'],
					'ol'         => ['id' => 'ref', 'class' => 'ref', 'start' => 'int'],
					'li'         => ['id' => 'ref', 'class' => 'ref'],
					'em'         => ['id' => 'ref', 'class' => 'ref'],
					'strong'     => ['id' => 'ref', 'class' => 'ref'],
					'hr'         => ['id' => 'ref', 'class' => 'ref'],
					'abbr'       => ['id' => 'ref', 'class' => 'ref', 'title' => 'text', 'aria-label' => 'text'],
					'del'        => ['id' => 'ref', 'class' => 'ref', 'cite' => 'url'],
					'ins'        => ['id' => 'ref', 'class' => 'ref', 'cite' => 'url'],
					'a'          => ['id' => 'ref', 'class' => 'ref', 'href' => 'url', 'target' => ['_blank'], 'rel' => ['noopener', 'noreferrer', 'nofollow']],
					'img'        => ['id' => 'ref', 'class' => 'ref', 'src' => 'url-img', 'alt' => 'text', 'width' => 'int', 'height' => 'int'],
					'figure'     => ['id' => 'ref', 'class' => 'ref'],
					'figcaption' => ['id' => 'ref', 'class' => 'ref'],

				];

			protected $parameters = [];

		//--------------------------------------------------
		// Setup

			public function __construct($template_html, $parameters = []) {

				//--------------------------------------------------
				// Check

					$this->literal_check($template_html);

				//--------------------------------------------------
				// Parameters

					$this->parameters = $parameters;

				//--------------------------------------------------
				// Simple Template HTML split

						// To keep this example short, the HTML is going to be parsed as XML
						// This is not intend a full/proper templating system.
						// The context of the placeholders is checked later
						// It uses a RegExp, which is *bad* for HTML, but it's fast, and can work with for this example.
						// The HTML must be a safe literal (a trusted string, from the developer, defined in the PHP script).
						// The HTML must include parameters in a Quoted Attribute, or it's own HTML Tag.
						// It only uses simple HTML Encoding - which is why attributes must be quoted, to avoid '<img src=? />' being used with 'x onerror=evil-js'

					$this->template_html = preg_split('/(?<=(>)|(\'|"))\?(?=(?(1)<\/|\2))/', $template_html);
					$this->template_end = (count($this->template_html) - 1);

						// Positive lookbehind assertion.
						//   For a '>' (1).
						//   Or a single/double quote (2).
						// The question mark for the parameter.
						// Positive lookahead assertion.
						//   When sub-pattern (1) matched, look for a '<'.
						//   Otherwise look for the same quote mark (2).

				//--------------------------------------------------
				// Primitive tag and attribute checking

					if (true) { // Can disable when running in production

							// Your HTML should be valid XML,
							// as it ensures strict/valid nesting,
							// attributes are quoted (important!),
							// and attributes cannot be redefined.
							//
							// You can use:
							//   '<img />' for self closing tags
							//   '<tag attribute="attribute">' for boolean attributes.

						$old = libxml_use_internal_errors(true); // "Disabling will also clear any existing libxml errors"...

						libxml_clear_errors(); // ... just kidding, not always.

						$html_prefix = '<?xml version="1.0" encoding="UTF-8"?><html>';
						$html_suffix = '</html>';

						$doc = new DomDocument();
						$doc->loadXML($html_prefix . $template_html . $html_suffix);

						foreach (libxml_get_errors() as $error) {
							libxml_clear_errors();
							throw new Exception('HTML Templates must be valid XML');
						}

						libxml_use_internal_errors($old);

						$this->template_parameters = [];

						$this->node_walk($doc);

						foreach ($this->template_parameters as $k => $p) {
							$allowed_attributes = ($this->template_allowed[$p[0]] ?? NULL);
							if ($allowed_attributes === NULL) {
								throw new Exception('Placeholder ' . ($k + 1) . ' is for unrecognised tag "' . $p[0] . '"');
							} else if ($p[1] === NULL) {
								// Content for a tag, so long as it's not an unsafe tag (e.g. <script>), it should be fine.
							} else if (($attribute_type = ($allowed_attributes[$p[1]] ?? NULL)) !== NULL) {
								$this->template_parameter_types[$k] = $attribute_type; // Generally fine, but check the type.
							} else if (str_starts_with($p[1], 'data-')) {
								// Can't tell, this is for JS/CSS to read and use.
							} else {
								throw new Exception('Placeholder ' . ($k + 1) . ' is for unrecognised tag "' . $p[0] . '" and attribute "' . $p[1] . '"');
							}
						}

					}

			}

		//--------------------------------------------------
		// Node walking

			private function node_walk($parent, $root = true) {
				foreach ($parent->childNodes as $node) {
					if ($node->nodeType === XML_TEXT_NODE) {
						if ($node->wholeText === '?') {
							$this->template_parameters[] = [$parent->nodeName, NULL];
						}
					} else if (!array_key_exists($node->nodeName, $this->template_allowed) && $root !== true) { // Skip for the root node
						throw new Exception('HTML Templates cannot use <' . $node->nodeName . '>');
					} else {
						if ($node->hasAttributes()) {
							$allowed_attributes = $this->template_allowed[$node->nodeName];
							foreach ($node->attributes as $attr) {
								if (!array_key_exists($attr->nodeName, $allowed_attributes) && !str_starts_with($attr->nodeName, 'data-')) {
									throw new Exception('HTML Templates cannot use the "' . $attr->nodeName . '" attribute in <' . $node->nodeName . '>');
								} else if ($node->nodeName === 'meta' && $attr->nodeName === 'name' && in_array($attr->nodeValue, ['?', 'referrer'])) {
									throw new Exception('HTML Templates cannot allow the "name" attribute in <meta> to be set to "' . $attr->nodeValue . '"');
								} else if ($attr->nodeValue === '?') {
									$this->template_parameters[] = [$node->nodeName, $attr->nodeName];
								}
							}
						}
						if ($node->hasChildNodes()) {
							$this->node_walk($node, false);
						}
					}
				}
			}

		//--------------------------------------------------
		// Output

			public function html($parameters = NULL) {

				if ($parameters === NULL) {
					$parameters = $this->parameters;
				}

				foreach ($this->template_parameter_types as $k => $type) { // Only populated (checked) during development
					if (!isset($parameters[$k])) {
						// Ignore this missing parameter, should be picked up next.
					} else if (is_array($type)) {
						$valid = true;
						if (!in_array($parameters[$k], $type)) {
							foreach (preg_split('/ +/', $parameters[$k]) as $token) { // supporting "space-separated tokens"
								if (!in_array($token, $type)) {
									$valid = false;
									break;
								}
							}
						}
						if (!$valid) {
							throw new Exception('Parameter ' . ($k + 1) . ' can only support the values "' . implode('", "', $type) . '".');
						}
					} else if ($type === 'text') {
						// Nothing to check
					} else if ($type === 'url-img' && ($parameters[$k] instanceof url_data) && substr($parameters[$k]->mime_get(), 0, 6) === 'image/') {
						// Images are allowed "data:" URLs with mime-types such as 'image/jpeg'
					} else if ($type === 'url' || $type === 'url-img') {
						// $parameters[$k] instanceof url
						if (substr($parameters[$k], 0, 1) !== '/') {
							throw new Exception('Parameter ' . ($k + 1) . ' should be a URL.');
						}
					} else if ($type === 'int') {
						if (!is_int($parameters[$k])) {
							throw new Exception('Parameter ' . ($k + 1) . ' should be an integer.');
						}
					} else if ($type === 'ref') {
						foreach (explode(' ', $parameters[$k]) as $ref) {
							$ref = trim($ref);
							if (!preg_match('/^[a-z][a-z0-9\-\_]+$/i', $ref)) { // Simple strings aren't checked outside of debug mode, but it might catch something during development.
								throw new Exception('Parameter ' . ($k + 1) . ' should be one or more valid references.');
							}
						}
					} else if ($type === 'datetime') {
						if (!preg_match('/^[0-9TWZPHMS \:\-\.\+]+$/i', $parameters[$k])) { // Could be better, but not important, as simple strings aren't checked outside of debug mode, and shouldn't be executed as JS by the browser... T=Time, W=Week, Z=Zulu, and PTHMS for duration
							throw new Exception('Parameter ' . ($k + 1) . ' should be a valid datetime.');
						}
					} else {
						throw new Exception('Parameter ' . ($k + 1) . ' has an unknown type.');
					}
				}

				$html = '';

				foreach ($this->template_html as $k => $template_html) {
					$html .= $template_html;
					if ($k < $this->template_end) {
						if (array_key_exists($k, $parameters)) { // Could be NULL
							$html .= htmlspecialchars(strval($parameters[$k]), (ENT_QUOTES | ENT_HTML5 | ENT_SUBSTITUTE | ENT_DISALLOWED), 'UTF-8');
						} else {
							throw new Exception('Missing parameter ' . ($k + 1));
						}
					} else if (isset($parameters[$k])) {
						throw new Exception('Extra parameter ' . ($k + 1));
					}
				}

				return $html; // or a new html_safe_value() value-object to show it's safe.

			}

			public function __toString() {
				return strval($this->html());
			}

	}

	class unsafe_value {
		private $value = '';
		function __construct($unsafe_value) {
			$this->value = $unsafe_value;
		}
		function __toString() {
			return $this->value;
		}
	}

	function ht($template_html, $parameters = []) {
		return new html_template($template_html, $parameters);
	}

//--------------------------------------------------
// Example 1

	$html = '
		<a href="?">
			<figure>
				<img src="?" width="?" height="?" alt="?" />
				<figcaption>?</figcaption>
			</figure>
		</a>';

	$parameters = [
			sprintf('/example/'), // javascript:alert(1)
			sprintf('/img/example.jpg'),
			100,
			200,
			sprintf('My Image Alt'),
			sprintf('My Image Caption'),
		];

	echo ht($html, $parameters);
	echo "\n\n";

//--------------------------------------------------
// Example 2

	$url = sprintf('/example/');
		// Using sprintf to mark as a non-literal string
		// INSECURE due to `javascript:` values

	echo ht('<a href="' . htmlspecialchars($url) . '">?</a>', [
			sprintf('My Link'),
		]);

?>