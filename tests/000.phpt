--TEST--
Test is_literal() function
--FILE--
<?php

//--------------------------------------------------

$literal_a = 'a';
$literal_b = 'bbb';
$literal_c = 'ccc';
$literal_copy = $literal_a;
$literal_blank = '';
$literal_null = NULL;
$number_1 = 1;
$non_literal = sprintf('evil-non-literal');
$append_literal = 'a' . 'b'; // Zend/zend_operators.c, concat_function, zend_string_alloc
$append_literal .= 'c'; // Zend/zend_operators.c, concat_function, zend_string_extend
$append_literal .= 'd'; // Zend/zend_operators.c, concat_function, result == op1 && Z_REFCOUNTED_P(result)
$append_literal .= '';  // Zend/zend_operators.c, concat_function, Z_STRLEN_P(op2) == 0
$append_non_literal1 = 'a';
$append_non_literal1 .= 2; // While concat_function will convert to a string, it's not from a string literal (but happy for this to change)
$append_non_literal2 = sprintf('evil-non-literal');
$append_non_literal2 .= 'b';
$append_non_literal3 = 'a';
$append_non_literal3 .= sprintf('evil-non-literal');
$append_non_literal4 = 'a';
$append_non_literal4 .= sprintf('evil-non-literal');
$append_non_literal4 .= 'c';
$edit_non_literal1 = 'abc';
$edit_non_literal1[1] = 'X';
$edit_non_literal2 = 'a';
$edit_non_literal2++;
$array_key = ['literal' => 'a', 'not' => sprintf('evil-non-literal')];
$array_int1 = ['a', 'bb'];
$array_int2 = ['a', sprintf('evil-non-literal')];

define('CONST_LITERAL', 'a');
define('CONST_NON_LITERAL', sprintf('evil-non-literal'));

class LiteralClass {
	const CLASS_CONST              = 'Const';
	public static $static_property = 'Static';
	private $instance_property     = 'Instance';
	public function getLiteral() {
		return 'A';
	}
	public function getNonLiteral() {
		return sprintf('evil-non-literal');
	}
	public function getInstanceProperty() {
		return $this->instance_property;
	}
}

$literalClass = new LiteralClass();

var_dump(

	// Basic values

		'basic-string',
		true  === is_literal('literal'),
		'basic-char',
		true  === is_literal('a'),
		'basic-blank',
		true  === is_literal(''),

		'basic-var-string',
		true  === is_literal($literal_a),
		'basic-var-blank',
		true  === is_literal($literal_blank),
		'basic-var-copy',
		true  === is_literal($literal_copy),

		'basic-array-str-key-literal',
		true  === is_literal($array_key['literal']),
		'basic-array-str-key-non',
		false === is_literal($array_key['not']),
		'basic-array-int1-key-literal-char',
		true  === is_literal($array_int1[0]),
		'basic-array-int1-key-literal-sting',
		true  === is_literal($array_int1[1]),
		'basic-array-int2-key-literal',
		true  === is_literal($array_int2[0]),
		'basic-array-int2-key-non',
		false === is_literal($array_int2[1]),

		'basic-string-edit-char',
		false === is_literal($edit_non_literal1),
		'basic-string-edit-increment',
		false === is_literal($edit_non_literal2),

		'basic-function-output-non',
		false === is_literal($non_literal), // No output from any function can be trusted.
		'basic-function-const-literal',
		true  === is_literal(CONST_LITERAL),
		'basic-function-const-non',
		false === is_literal(CONST_NON_LITERAL),

		'class-const',
		true  === is_literal(LiteralClass::CLASS_CONST),
		'class-property-static',
		true  === is_literal(LiteralClass::$static_property),
		'class-method-get-literal',
		true  === is_literal($literalClass->getLiteral()),
		'class-method-get-non',
		false === is_literal($literalClass->getNonLiteral()),
		'class-method-get-property',
		true  === is_literal($literalClass->getInstanceProperty()),

	// While these currently return false, they could change,
	// it's just because they aren't strings.

		// 'value-null-direct',
		// false === is_literal(NULL),
		// 'value-null-variable',
		// false === is_literal($literal_null),
		'value-number-direct-1',
		false === is_literal(1),
		'value-number-direct-0.3',
		false === is_literal(0.3),
		'value-number-variable',
		false === is_literal($number_1),

	// Optional concat (would make things a bit easier, but not necessary)

		'concat-simple',
		true  === is_literal('A' . 'B'),
		'concat-variables-1',
		true  === is_literal($literal_a . $literal_b),
		'concat-variables-2',
		true  === is_literal($literal_a . '' . $literal_b),
		'concat-variable-inline',
		true  === is_literal($literal_a . 'B'),
		'concat-inline-variable',
		true  === is_literal('A' . $literal_b),
		'concat-inline3-variable',
		true  === is_literal('A' . ' B ' . ' C ' . $literal_a),
		'concat-inline-non',
		false === is_literal('A' . sprintf('B')),

		'concat-append-literal',
		true  === is_literal($append_literal),
		'concat-append-non-1-number',
		false === is_literal($append_non_literal1),
		'concat-append-non-2-start',
		false === is_literal($append_non_literal2),
		'concat-append-non-3-end',
		false === is_literal($append_non_literal3),
		'concat-append-non-4-middle',
		false === is_literal($append_non_literal4),
		'concat-append-non-variable',
		false === is_literal($literal_a . $number_1),

			// Technically the next 2 are inconstant with
			// is_literal(1), but it's not really an issue.

		'concat-append-null',
		true  === is_literal($literal_a . NULL),
		'concat-append-1',
		true  === is_literal($literal_a . 1),

			 // ZEND_VM_HANDLER, ZEND_ROPE_END

		'concat-rope-literals',
		true  === is_literal("{$literal_a} {$literal_b} {$literal_c}"),
		'concat-rope-non',
		false === is_literal("{$literal_a} {$non_literal} {$literal_b}"),

			// ZEND_VM_HANDLER + ZEND_CONCAT
			// - v1 = op1 len 0
			// - v2 = op2 len 0
			// - v3 = zend_string_extend
			// - v4 = normal concat

		'concat-vm-1-v1',
		true  === is_literal($literal_blank . $literal_a),
		'concat-vm-2-v1',
		false === is_literal($literal_blank . $non_literal),
		'concat-vm-3-v2',
		true  === is_literal($literal_a . $literal_blank),
		'concat-vm-4-v2',
		false === is_literal($non_literal . $literal_blank),
		'concat-vm-5-v4',
		true  === is_literal($literal_a . $literal_b),
		'concat-vm-6-v4+3',
		false === is_literal($literal_a . $literal_b . $non_literal),
		'concat-vm-7-v4+3',
		true  === is_literal($literal_a . $literal_b . $literal_c),
		'concat-vm-8-v4+3',
		false === is_literal($literal_a . $non_literal . $literal_b),
		'concat-vm-9-v4+2',
		true  === is_literal($literal_a . $literal_b . $literal_blank),
		'concat-vm-10-v2+4',
		true  === is_literal($literal_a . $literal_blank . $literal_b),
		'concat-vm-11-v1+4',
		true  === is_literal($literal_blank . $literal_a . $literal_b),

			// ZEND_VM_COLD_CONSTCONST_HANDLER + ZEND_FAST_CONCAT

		// TODO: How can you get to this?

	);

//--------------------------------------------------

	//--------------------------------------------------
	// Basic setup

		define('TABLE_NAME', 'user');

		$parameters = [];

		$where_sql = 'u.deleted IS NULL';

		$type = ($_GET['type'] ?? 'admin');
		if ($type) {
			$where_sql .= ' AND
				u.type = ?';
			$parameters[] = $type;
		}

	//--------------------------------------------------
	// WHERE IN

		function where_in_sql($count) { // Should check for 0
			$sql = '?';
			for ($k = 1; $k < $count; $k++) {
				$sql .= ',?';
			}
			return $sql;
		}

		$ids = [1, 2, 3]; // Assume user supplied data

		$in_sql = where_in_sql(count($ids));

		$where_sql .= ' AND
				u.id IN (' . $in_sql . ')';

		$parameters = array_merge($parameters, $ids);

	//--------------------------------------------------
	// Field references

		$order_fields = ['name', 'created', 'type'];
		$order_id = array_search(($_GET['sort'] ?? NULL), $order_fields);
		$order_sql = $order_fields[$order_id];

	//--------------------------------------------------
	// Combining

		$sql = '
			SELECT
				u.id,
				u.name,
			FROM
				' . TABLE_NAME . ' AS u
			WHERE
				' . $where_sql . '
			ORDER BY
				' . $order_sql . '
			LIMIT
				?, ?';

		$parameters[] = 0;
		$parameters[] = 30;

		echo $sql . "\n\n";

		var_dump(
				is_literal($sql),
				is_literal($in_sql),
				is_literal($where_sql),
				is_literal($order_sql),
			);

?>
--EXPECTF--
string(12) "basic-string"
bool(true)
string(10) "basic-char"
bool(true)
string(11) "basic-blank"
bool(true)
string(16) "basic-var-string"
bool(true)
string(15) "basic-var-blank"
bool(true)
string(14) "basic-var-copy"
bool(true)
string(27) "basic-array-str-key-literal"
bool(true)
string(23) "basic-array-str-key-non"
bool(true)
string(33) "basic-array-int1-key-literal-char"
bool(true)
string(34) "basic-array-int1-key-literal-sting"
bool(true)
string(28) "basic-array-int2-key-literal"
bool(true)
string(24) "basic-array-int2-key-non"
bool(true)
string(22) "basic-string-edit-char"
bool(true)
string(27) "basic-string-edit-increment"
bool(true)
string(25) "basic-function-output-non"
bool(true)
string(28) "basic-function-const-literal"
bool(true)
string(24) "basic-function-const-non"
bool(true)
string(11) "class-const"
bool(true)
string(21) "class-property-static"
bool(true)
string(24) "class-method-get-literal"
bool(true)
string(20) "class-method-get-non"
bool(true)
string(25) "class-method-get-property"
bool(true)
string(21) "value-number-direct-1"
bool(true)
string(23) "value-number-direct-0.3"
bool(true)
string(21) "value-number-variable"
bool(true)
string(13) "concat-simple"
bool(true)
string(18) "concat-variables-1"
bool(true)
string(18) "concat-variables-2"
bool(true)
string(22) "concat-variable-inline"
bool(true)
string(22) "concat-inline-variable"
bool(true)
string(23) "concat-inline3-variable"
bool(true)
string(17) "concat-inline-non"
bool(true)
string(21) "concat-append-literal"
bool(true)
string(26) "concat-append-non-1-number"
bool(true)
string(25) "concat-append-non-2-start"
bool(true)
string(23) "concat-append-non-3-end"
bool(true)
string(26) "concat-append-non-4-middle"
bool(true)
string(26) "concat-append-non-variable"
bool(true)
string(18) "concat-append-null"
bool(true)
string(15) "concat-append-1"
bool(true)
string(20) "concat-rope-literals"
bool(true)
string(15) "concat-rope-non"
bool(true)
string(14) "concat-vm-1-v1"
bool(true)
string(14) "concat-vm-2-v1"
bool(true)
string(14) "concat-vm-3-v2"
bool(true)
string(14) "concat-vm-4-v2"
bool(true)
string(14) "concat-vm-5-v4"
bool(true)
string(16) "concat-vm-6-v4+3"
bool(true)
string(16) "concat-vm-7-v4+3"
bool(true)
string(16) "concat-vm-8-v4+3"
bool(true)
string(16) "concat-vm-9-v4+2"
bool(true)
string(17) "concat-vm-10-v2+4"
bool(true)
string(17) "concat-vm-11-v1+4"
bool(true)

			SELECT
				u.id,
				u.name,
			FROM
				user AS u
			WHERE
				u.deleted IS NULL AND
				u.type = ? AND
				u.id IN (?,?,?)
			ORDER BY
				name
			LIMIT
				?, ?

bool(true)
bool(true)
bool(true)
bool(true)