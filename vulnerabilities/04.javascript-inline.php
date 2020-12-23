<?php

	$url = '</script><script>alert(1)</script>';

?>

	<script>
		var url = "<?= addslashes($url) ?>";
	</script>
