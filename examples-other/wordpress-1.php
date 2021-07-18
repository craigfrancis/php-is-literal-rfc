<?php



	// Ignore!

	// This is a very basic proof of concept,
	// one that's just focused on making migration
	// as easy as possible for existing code.



//--------------------------------------------------
// The library

	class wpdb {

		function esc_like($text) {
			return addcslashes( $text, '_%\\' );
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

	class wp_query {

		private $fragments = [];
		private $literals = ['0','1','2','3','4','5','6','7','8','9'];

		function prepare($input_sql, ...$args ) {

			//--------------------------------------------------
			// Checks

				if (!is_literal($input_sql)) {
					trigger_error('Non-literal value detected!', E_USER_WARNING);
				}

			//--------------------------------------------------
			// Ref

				$ref = '{FRAGMENT:';
				foreach (str_split(count($this->fragments)) as $i) {
					$ref .= $this->literals[$i];
				}
				$ref .= '}';

			//--------------------------------------------------
			// Fragment parsing

				$output_sql = [];
				$parameters = [];

				preg_match_all('/(?<!%)%([sdf])/', $input_sql, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE);

				foreach (array_reverse($matches, true) as $k => $match) {

					$kind = $match[1][0]; // s/d/f from the first sub-pattern.
					$length = strlen($match[0][0]); // This basic version would always be 2.
					$start = $match[0][1];

					$parameters[] = $args[$k]; // Might be useful to keep $kind?

					$output_sql[] = '?' . substr($input_sql, ($start + $length));

					$input_sql = substr($input_sql, 0, $start);

				}

				$output_sql[] = $input_sql;
				$output_sql = array_reverse($output_sql);
				$output_sql = implode('', $output_sql);

				// $parameters = array_reverse($parameters);

				$this->fragments[] = ['sql' => $output_sql, 'parameters' => $parameters];

			//--------------------------------------------------
			// Return

				return $ref;

		}

		function execute($input_sql) {

			//--------------------------------------------------
			// Checks

				if (!is_literal($input_sql)) {
					trigger_error('Non-literal value detected!', E_USER_WARNING);
				}

				$output_sql = [];
				$parameters = [];

			//--------------------------------------------------
			// Apply fragments

				preg_match_all('/{FRAGMENT:([0-9])}/', $input_sql, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE);

				foreach (array_reverse($matches, true) as $k => $match) {

					$fragment = $this->fragments[$match[1][0]];
					$length = strlen($match[0][0]);
					$start = $match[0][1];

					$output_sql[] = $fragment['sql'] . substr($input_sql, ($start + $length));

					$parameters = array_merge($parameters, $fragment['parameters']);

					$input_sql = substr($input_sql, 0, $start);

				}

				$output_sql[] = $input_sql;
				$output_sql = array_reverse($output_sql);
				$output_sql = implode('', $output_sql);

				$parameters = array_reverse($parameters);

			//--------------------------------------------------
			// Execute Query

				print_r($output_sql);
				print_r($parameters);

		}

	}

//--------------------------------------------------
// Example

		// https://github.com/woocommerce/woocommerce/blob/trunk/includes/data-stores/class-wc-webhook-data-store.php#L238

	$wpdb = new wpdb();
	$query = new wp_query(); // New thing!

	$args = array(
			'limit'    => 10,
			'offset'   => 0,
			'order'    => 'DESC',
			'orderby'  => 'id',
			'paginate' => false,
			'status'   => 'example-status',
			'search'   => 'example-search',
		);

	$wpdb_prefix = 'wp_';

	$orderby_mapping = array(
			'ID'            => 'webhook_id',
			'id'            => 'webhook_id',
			'name'          => 'name',
			'title'         => 'name',
			'post_title'    => 'name',
			'post_name'     => 'name',
			'date_created'  => 'date_created_gmt',
			'date'          => 'date_created_gmt',
			'post_date'     => 'date_created_gmt',
			'date_modified' => 'date_modified_gmt',
			'modified'      => 'date_modified_gmt',
			'post_modified' => 'date_modified_gmt',
		);

	$orderby         = isset( $orderby_mapping[ $args['orderby'] ] ) ? $orderby_mapping[ $args['orderby'] ] : 'webhook_id';
	$sort            = 'ASC' === strtoupper( $args['order'] ) ? 'ASC' : 'DESC';
	$order           = "ORDER BY {$orderby} {$sort}";
	$limit           = -1 < $args['limit'] ? $query->prepare( 'LIMIT %d', $args['limit'] ) : '';
	$offset          = 0 < $args['offset'] ? $query->prepare( 'OFFSET %d', $args['offset'] ) : '';
	$status          = ! empty( $args['status'] ) ? $query->prepare( 'AND `status` = %s', isset( $statuses[ $args['status'] ] ) ? $statuses[ $args['status'] ] : $args['status'] ) : '';
	$search          = ! empty( $args['search'] ) ? $query->prepare( "AND `name` LIKE %s", '%' . $wpdb->esc_like( $args['search'] ) . '%' ) : '';
	$include         = '';
	$exclude         = '';
	$date_created    = '';
	$date_modified   = '';

	// $extra = $query->prepare(' AND id = %d AND age > %d AND name LIKE %s', 123, 20, 'example-name%');
	$extra = '';

	$sql = "SELECT webhook_id
		FROM {$wpdb_prefix}wc_webhooks
		WHERE 1=1
		{$status}
		{$search}
		{$include}
		{$exclude}
		{$date_created}
		{$date_modified}
		{$extra}
		{$order}
		{$limit}
		{$offset}";

	$query->execute($sql);

?>