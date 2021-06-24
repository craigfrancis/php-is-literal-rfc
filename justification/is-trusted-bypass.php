<?php

// You should NEVER need this.

// If a library expects a noble value, you should write it.

// The library will expect user values to be provided separately.

// Trying to work around that is dangerous!

// However, if you really need to pretend that your
// unsafe/dangerous value is noble, you can:

	function unsafe_pretend_this_is_noble(&$value) {
		eval('$value = ' . var_export($value, true) . ';');
	}

// Again, you should NEVER need this.

?>