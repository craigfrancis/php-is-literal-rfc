<?php

	class html_template {

		//--------------------------------------------------
		// Variables

			protected $template_html = [];
			protected $template_end = NULL;
			protected $template_parameters = NULL;
			protected $template_parameter_types = [];
			protected $template_allowed = [ // Do not allow <script>, <style>, <link>, <object>, <embed> tags; or attributes that can include JS (e.g. style, onload, dynsrc)... although some can accept url(x) values
					'meta'       => ['name' => 'text', 'content' => 'text'], // Do not allow <meta http-equiv="">, e.g. Refresh, Set-Cookie
					'div'        => ['id' => 'ref', 'class' => 'ref', 'role' => 'text', 'title' => 'text', 'tabindex' => 'int'],
					'span'       => ['id' => 'ref', 'class' => 'ref', 'role' => 'text', 'title' => 'text', 'tabindex' => 'int'],
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
					'dl'         => ['id' => 'ref', 'class' => 'ref'],
					'dt'         => ['id' => 'ref', 'class' => 'ref'],
					'dd'         => ['id' => 'ref', 'class' => 'ref'],
					'pre'        => ['id' => 'ref', 'class' => 'ref'],
					'table'      => ['id' => 'ref', 'class' => 'ref'],
					'caption'    => ['id' => 'ref', 'class' => 'ref'],
					'thead'      => ['id' => 'ref', 'class' => 'ref'],
					'tbody'      => ['id' => 'ref', 'class' => 'ref'],
					'tfoot'      => ['id' => 'ref', 'class' => 'ref'],
					'tr'         => ['id' => 'ref', 'class' => 'ref'],
					'th'         => ['id' => 'ref', 'class' => 'ref', 'rowspan' => 'int', 'colspan' => 'int', 'scope' => 'text'],
					'td'         => ['id' => 'ref', 'class' => 'ref', 'rowspan' => 'int', 'colspan' => 'int'],
					'em'         => ['id' => 'ref', 'class' => 'ref', 'title' => 'text'],
					'strong'     => ['id' => 'ref', 'class' => 'ref', 'title' => 'text'],
					'hr'         => ['id' => 'ref', 'class' => 'ref'],
					'sub'        => ['id' => 'ref', 'class' => 'ref'],
					'sup'        => ['id' => 'ref', 'class' => 'ref'],
					'abbr'       => ['id' => 'ref', 'class' => 'ref', 'title' => 'text', 'aria-label' => 'text'],
					'cite'       => ['id' => 'ref', 'class' => 'ref'],
					'code'       => ['id' => 'ref', 'class' => 'ref'],
					'samp'       => ['id' => 'ref', 'class' => 'ref'],
					'mark'       => ['id' => 'ref', 'class' => 'ref'],
					'var'        => ['id' => 'ref', 'class' => 'ref'],
					'wbr'        => ['id' => 'ref', 'class' => 'ref'],
					'del'        => ['id' => 'ref', 'class' => 'ref', 'cite' => 'url'],
					'ins'        => ['id' => 'ref', 'class' => 'ref', 'cite' => 'url'],
					'blockquote' => ['id' => 'ref', 'class' => 'ref', 'cite' => 'url'],
					'q'          => ['id' => 'ref', 'class' => 'ref', 'cite' => 'url'],
					'a'          => ['id' => 'ref', 'class' => 'ref', 'href' => 'url'],
					'img'        => ['id' => 'ref', 'class' => 'ref', 'src' => 'url-img', 'alt' => 'text', 'width' => 'int', 'height' => 'int'],
					'time'       => ['id' => 'ref', 'class' => 'ref', 'datetime' => 'datetime'],
					'data'       => ['id' => 'ref', 'class' => 'ref', 'value' => 'text'],
					'br'         => [],
				];

			protected $parameters = [];

		//--------------------------------------------------
		// Setup

			public function __construct($template_html, $parameters = []) {

				//--------------------------------------------------
				// Parameters

					$this->parameters = $parameters;

				//--------------------------------------------------
				// Template HTML

					if (!is_trusted($template_html)) {
						throw new Exception('Invalid Template HTML.');
					}

						// This does not intend to be a full/proper templating system.
						// The context of the placeholders is only roughly checked, when in debug mode, via XML parsing.
						// It uses a RegExp, which is bad for general HTML, but it's fast, and can work with known-good XHTML (in theory).
						// The HTML must be a safe literal (a trusted string, from the developer, defined in the PHP script).
						// The HTML must be valid XML (why be lazy/messy?).
						// The parameters must be in a Quoted Attribute, or their own HTML Tag.
						// It only uses simple HTML Encoding - which is why attributes must be quoted, to avoid '<img src=? />' being used with 'x onerror=evil-js'

					$this->template_html = preg_split('/(?<=(>)|(\'|"))\?(?=(?(1)<|\2))/', $template_html);
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

					if (DEBUG_MODE) {

							// Your HTML should be valid XML,
							// as it ensures strict/valid nesting,
							// attributes are quoted (important!),
							// and attributes cannot be redefined.
							//
							// You can use:
							//   '<img />' for self closing tags
							//   '<tag attribute="attribute">' for boolean attributes.

						$old = libxml_use_internal_errors(true); // "Disabling will also clear any existing libxml errors"

						$html_prefix = '<?xml version="1.0" encoding="UTF-8"?><html>';
						$html_suffix = '</html>';

						$doc = new DomDocument();
						$doc->loadXML($html_prefix . $template_html . $html_suffix);

						foreach (libxml_get_errors() as $error) {
							libxml_clear_errors();
							throw new exception('HTML Templates must be valid XML' . "\n" . trim($error->message) . ' (line ' . $error->line . ':' . (intval($error->column) - strlen($html_prefix)) . ')' . "\n" . $template_html);
						}

						libxml_use_internal_errors($old);

						$this->template_parameters = [];

						$this->node_walk($doc);

						foreach ($this->template_parameters as $k => $p) {
							$allowed_attributes = ($this->template_allowed[$p[0]] ?? NULL);
							if ($allowed_attributes === NULL) {
								throw new exception('Placeholder ' . ($k + 1) . ' is for unrecognised tag "' . $p[0] . '"' . "\n" . $template_html);
							} else if ($p[1] === NULL) {
								// Content for a tag, so long as it's not an unsafe tag (e.g. <script>), it should be fine.
							} else if (($attribute_type = ($allowed_attributes[$p[1]] ?? NULL)) !== NULL) {
								$this->template_parameter_types[$k] = $attribute_type; // Generally fine, but check the type.
							} else if (str_starts_with($p[1], 'data-')) {
								// Can't tell, this is for JS/CSS to read and use.
							} else {
								throw new exception('Placeholder ' . ($k + 1) . ' is for unrecognised tag "' . $p[0] . '" and attribute "' . $p[1] . '"' . "\n" . $template_html);
							}
						}

					}

			}

		//--------------------------------------------------
		// Node walking

			private function node_walk($parent, $root = true) {
				foreach ($parent->childNodes as $node) {
					if ($node->nodeType === XML_TEXT_NODE) {
						if ($node->wholeText == '?') {
							$this->template_parameters[] = [$parent->nodeName, NULL];
						}
					} else if (!array_key_exists($node->nodeName, $this->template_allowed) && $root !== true) { // Skip for the root node
						throw new exception('HTML Templates cannot use <' . $node->nodeName . '>' . "\n" . implode('?', $this->template_html));
					} else {
						if ($node->hasAttributes()) {
							$allowed_attributes = $this->template_allowed[$node->nodeName];
							foreach ($node->attributes as $attr) {
								if (!array_key_exists($attr->nodeName, $allowed_attributes) && !str_starts_with($attr->nodeName, 'data-')) {
									throw new exception('HTML Templates cannot use the "' . $attr->nodeName . '" attribute in <' . $node->nodeName . '>' . "\n" . implode('?', $this->template_html));
								} else if ($node->nodeName == 'meta' && $attr->nodeName == 'name' && in_array($attr->nodeValue, ['?', 'referrer'])) {
									throw new exception('HTML Templates cannot allow the "name" attribute in <meta> to be set to "' . $attr->nodeValue . '"' . "\n" . implode('?', $this->template_html));
								} else if ($attr->nodeValue == '?') {
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

				foreach ($this->template_parameter_types as $k => $type) {
					if (!isset($parameters[$k])) {
						// Ignore this missing parameter, should be picked up next.
					} else if ($type == 'text') {
						// Nothing to check
					} else if ($type == 'url-img' && ($parameters[$k] instanceof url_data) && substr($parameters[$k]->mime_get(), 0, 6) == 'image/') {
						// Images are allowed "data:" URLs with mime-types such as 'image/jpeg'
					} else if ($type == 'url' || $type == 'url-img') {
						if (!($parameters[$k] instanceof url) && !($parameters[$k] instanceof url_immutable)) {
							throw new exception('Parameter ' . ($k + 1) . ' should be a URL object.' . "\n" . var_export($parameters[$k], true) . "\n" . implode('?', $this->template_html));
						}
					} else if ($type == 'int') {
						if (!is_int($parameters[$k])) {
							throw new exception('Parameter ' . ($k + 1) . ' should be an integer.' . "\n" . var_export($parameters[$k], true) . "\n" . implode('?', $this->template_html));
						}
					} else if ($type == 'ref') {
						foreach (explode(' ', $parameters[$k]) as $ref) {
							$ref = trim($ref);
							if (!preg_match('/^[a-z][a-z0-9\-\_]+$/i', $ref)) { // Simple strings aren't checked outside of debug mode, but it might catch something during development.
								throw new exception('Parameter ' . ($k + 1) . ' should be one or more valid references.' . "\n" . var_export($ref, true) . "\n" . implode('?', $this->template_html));
							}
						}
					} else if ($type == 'datetime') {
						if (!preg_match('/^[0-9TWZPHMS \:\-\.\+]+$/i', $parameters[$k])) { // Could be better, but not important, as simple strings aren't checked outside of debug mode, and shouldn't be executed as JS by the browser... T=Time, W=Week, Z=Zulu, and PTHMS for duration
							throw new exception('Parameter ' . ($k + 1) . ' should be a valid datetime.' . "\n" . var_export($parameters[$k], true) . "\n" . implode('?', $this->template_html));
						}
					} else {
						throw new exception('Parameter ' . ($k + 1) . ' has an unknown type.' . "\n" . $type . "\n" . var_export($parameters[$k], true) . "\n" . implode('?', $this->template_html));
					}
				}

				$html = '';

				foreach ($this->template_html as $k => $template_html) {
					$html .= $template_html;
					if ($k < $this->template_end) {
						if (array_key_exists($k, $parameters)) { // Could be NULL
							$html .= nl2br(html($parameters[$k]));
						} else {
							throw new exception('Missing parameter ' . ($k + 1) . "\n" . implode('?', $this->template_html));
						}
					} else if (isset($parameters[$k])) {
						throw new exception('Extra parameter ' . ($k + 1) . "\n" . implode('?', $this->template_html));
					}
				}

				return new html_template_immutable($html, 'createdByHtmlTemplateClass');

			}

		//--------------------------------------------------
		// String shorthand

			public function __toString() {
				return strval($this->html());
			}

	}

	class html_template_immutable implements JsonSerializable {

		private $value = NULL;

		public function __construct($value, $source) {
			$this->value = $value;
			if ($source != 'createdByHtmlTemplateClass') {
				throw new exception('Do not create a "html_template_immutable" object directly, use a "html_template" helper.');
			}
		}

		public function __toString() {
			return $this->value;
		}

		public function jsonSerialize() { // If JSON encoded, fall back to being a simple string (typically going to the browser or API)
			return $this->value;
		}

	}

	function ht($template_html, $parameters = []) {
		return new html_template($template_html, $parameters);
	}

	function html($text) {
		return htmlspecialchars($text, (ENT_QUOTES | ENT_HTML5 | ENT_SUBSTITUTE | ENT_DISALLOWED), 'UTF-8');
	}

?>