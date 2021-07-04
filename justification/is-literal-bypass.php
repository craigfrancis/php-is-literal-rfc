<?php

// You should NEVER need this.

// If a library expects a literal value, you should write it.

// The library will expect user values to be provided separately.

// Trying to work around that is dangerous!

// However, if you really need to pretend that your
// unsafe/dangerous value is literal, you can:

	function unsafe_pretend_this_is_literal($value) {
		eval('$value = ' . var_export(strval($value), true) . ';');
		return $value;
	}

// Again, you should NEVER need this.

?>