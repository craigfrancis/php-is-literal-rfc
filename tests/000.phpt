--TEST--
Test is_trusted() function
--FILE--
<?php

//--------------------------------------------------

$literal_a = 'a';
$literal_b = 'bbb';
$literal_c = 'ccc';
$literal_copy = $literal_a;
$literal_blank = '';
$literal_null = NULL;
$number_1 = 123;
$non_literal = strtoupper('evil-non-literal');
$append_literal1 = 'a' . 'b'; // Zend/zend_operators.c, concat_function, zend_string_alloc
$append_literal1 .= 'c'; // Zend/zend_operators.c, concat_function, zend_string_extend
$append_literal1 .= 'd'; // Zend/zend_operators.c, concat_function, result == op1 && Z_REFCOUNTED_P(result)
$append_literal1 .= '';  // Zend/zend_operators.c, concat_function, Z_STRLEN_P(op2) == 0
$append_literal2 = 'a';
$append_literal2 .= 2; // Integers are handled a bit differently
$append_non_literal1 = strtoupper('evil-non-literal');
$append_non_literal1 .= 'b';
$append_non_literal2 = 'a';
$append_non_literal2 .= strtoupper('evil-non-literal');
$append_non_literal3 = 'a';
$append_non_literal3 .= strtoupper('evil-non-literal');
$append_non_literal3 .= 'c';
$edit_non_literal1 = 'abc';
$edit_non_literal1[1] = 'X';
$edit_non_literal2 = 'a';
$edit_non_literal2++;
$array_key = ['literal' => 'a', 'not' => strtoupper('evil-non-literal')];
$array_int1 = ['a', 'bb'];
$array_int2 = ['a', strtoupper('evil-non-literal')];

define('CONST_LITERAL', 'a');
define('CONST_NON_LITERAL', strtoupper('evil-non-literal'));

class LiteralClass {
	const CLASS_CONST              = 'Const';
	public static $static_property = 'Static';
	private $instance_property     = 'Instance';
	public function getLiteral() {
		return 'A';
	}
	public function getNonLiteral() {
		return strtoupper('evil-non-literal');
	}
	public function getInstanceProperty() {
		return $this->instance_property;
	}
}

$literalClass = new LiteralClass();

var_dump(

	// Basic values

		'basic-string',
		true  === is_trusted('literal'),
		'basic-int',
		true  === is_trusted(1),
		'basic-char',
		true  === is_trusted('a'),
		'basic-blank',
		true  === is_trusted(''),
		'basic-null',
		false === is_trusted(NULL),

		'basic-var-string',
		true  === is_trusted($literal_a),
		'basic-var-int',
		true  === is_trusted($number_1),
		'basic-var-blank',
		true  === is_trusted($literal_blank),
		'basic-var-copy',
		true  === is_trusted($literal_copy),

		'basic-array-str-key-literal',
		true  === is_trusted($array_key['literal']),
		'basic-array-str-key-non',
		false === is_trusted($array_key['not']),
		'basic-array-int1-key-literal-char',
		true  === is_trusted($array_int1[0]),
		'basic-array-int1-key-literal-sting',
		true  === is_trusted($array_int1[1]),
		'basic-array-int2-key-literal',
		true  === is_trusted($array_int2[0]),
		'basic-array-int2-key-non',
		false === is_trusted($array_int2[1]),

		'basic-string-edit-char',
		false === is_trusted($edit_non_literal1),
		'basic-string-edit-increment',
		false === is_trusted($edit_non_literal2),

		'basic-function-output-non',
		false === is_trusted($non_literal), // No output from any function can be trusted.
		'basic-function-const-literal',
		true  === is_trusted(CONST_LITERAL),
		'basic-function-const-non',
		false === is_trusted(CONST_NON_LITERAL),

		'class-const',
		true  === is_trusted(LiteralClass::CLASS_CONST),
		'class-property-static',
		true  === is_trusted(LiteralClass::$static_property),
		'class-method-get-literal',
		true  === is_trusted($literalClass->getLiteral()),
		'class-method-get-non',
		false === is_trusted($literalClass->getNonLiteral()),
		'class-method-get-property',
		true  === is_trusted($literalClass->getInstanceProperty()),

	// No supported

		'value-null-direct',
		false === is_trusted(NULL),
		'value-null-variable',
		false === is_trusted($literal_null),
		'value-number-direct-boolean-1',
		false === is_trusted(true), // String conversion would be to '1'
		'value-number-direct-boolean-1',
		false === is_trusted(false), // String conversion would be to ''
		'value-number-direct-float-1',
		false === is_trusted(0.3), // locale can sometimes use ',' for the decimal place.
		'value-number-direct-float-2',
		false === is_trusted(2.3 * 100), // Converted to '229.99999999999997'

	// Concatenation

		'concat-simple',
		true  === is_trusted('A' . 'B'),
		'concat-variables-1',
		true  === is_trusted($literal_a . $literal_b),
		'concat-variables-2',
		true  === is_trusted($literal_a . '' . $literal_b),
		'concat-variable-inline',
		true  === is_trusted($literal_a . 'B'),
		'concat-inline-variable',
		true  === is_trusted('A' . $literal_b),
		'concat-inline3-variable',
		true  === is_trusted('A' . ' B ' . ' C ' . $literal_a),
		'concat-inline-non',
		false === is_trusted('A' . strtoupper('evil-non-literal')),

		'concat-append-literal-1',
		true  === is_trusted($append_literal1),
		'concat-append-literal-2',
		true  === is_trusted($append_literal2),
		'concat-append-non-1-start',
		false === is_trusted($append_non_literal1),
		'concat-append-non-2-end',
		false === is_trusted($append_non_literal2),
		'concat-append-non-3-middle',
		false === is_trusted($append_non_literal3),
		'concat-append-non',
		false === is_trusted($literal_a . strtoupper('evil-non-literal')),
		'concat-append-int',
		true  === is_trusted($literal_a . 1),
		'concat-append-int-var-1',
		true  === is_trusted($literal_a . $number_1),
		'concat-append-int-var-2',
		true  === is_trusted("$literal_a $number_1"),
		'concat-append-null',
		true  === is_trusted($literal_a . NULL),

			 // ZEND_VM_HANDLER, ZEND_ROPE_END

		'concat-rope-literals',
		true  === is_trusted("{$literal_a} {$literal_b} {$literal_c}"),
		'concat-rope-non',
		false === is_trusted("{$literal_a} {$non_literal} {$literal_b}"),

			// ZEND_VM_HANDLER + ZEND_CONCAT
			// - v1 = op1 len 0
			// - v2 = op2 len 0
			// - v3 = zend_string_extend
			// - v4 = normal concat

		'concat-vm-1-v1',
		true  === is_trusted($literal_blank . $literal_a),
		'concat-vm-2-v1',
		false === is_trusted($literal_blank . $non_literal),
		'concat-vm-3-v2',
		true  === is_trusted($literal_a . $literal_blank),
		'concat-vm-4-v2',
		false === is_trusted($non_literal . $literal_blank),
		'concat-vm-5-v4',
		true  === is_trusted($literal_a . $literal_b),
		'concat-vm-6-v4+3',
		false === is_trusted($literal_a . $literal_b . $non_literal),
		'concat-vm-7-v4+3',
		true  === is_trusted($literal_a . $literal_b . $literal_c),
		'concat-vm-8-v4+3',
		false === is_trusted($literal_a . $non_literal . $literal_b),
		'concat-vm-9-v4+2',
		true  === is_trusted($literal_a . $literal_b . $literal_blank),
		'concat-vm-10-v2+4',
		true  === is_trusted($literal_a . $literal_blank . $literal_b),
		'concat-vm-11-v1+4',
		true  === is_trusted($literal_blank . $literal_a . $literal_b),

			// ZEND_VM_COLD_CONSTCONST_HANDLER + ZEND_FAST_CONCAT
			// TODO: How can you get to this?

	// Concat functions

		'concat-str_repeat-literal',
		true  === is_trusted(str_repeat($literal_a, 10)),
		'concat-str_repeat-non',
		false === is_trusted(str_repeat($non_literal, 10)),

		'concat-str_pad-literal',
		true  === is_trusted(str_pad($literal_a, 10, '-')),
		'concat-str_pad-non',
		false === is_trusted(str_pad($literal_a, 10, $non_literal)),

		'concat-implode-literal',
		true  === is_trusted(implode(' AND ', [$literal_a, $literal_b])),
		'concat-implode-none',
		false === is_trusted(implode(' AND ', [$literal_a, $non_literal])),

		'concat-array_pad-source-literal',
		true  === is_trusted(array_pad([$literal_a], 10, $literal_b)[0]),
		'concat-array_pad-value-literal',
		true  === is_trusted(array_pad([$literal_a], 10, $literal_b)[5]),
		'concat-array_pad-value-non-1',
		true  === is_trusted(array_pad([$literal_a], 10, $non_literal)[0]),
		'concat-array_pad-value-non-2',
		false === is_trusted(array_pad([$literal_a], 10, $non_literal)[5]),
		'concat-array_pad-source-literal-1',
		true  === is_trusted(array_pad([$literal_a, $non_literal], 10, $literal_b)[0]),
		'concat-array_pad-source-literal-2',
		false === is_trusted(array_pad([$literal_a, $non_literal], 10, $literal_b)[1]),
		'concat-array_pad-source-literal-3',
		true  === is_trusted(array_pad([$literal_a, $non_literal], 10, $literal_b)[5]),

		'concat-array_fill-value-literal',
		true  === is_trusted(array_fill(0, 10, $literal_a)[5]),
		'concat-array_fill-value-non',
		false === is_trusted(array_fill(0, 10, $non_literal)[5]),

		'sprintf-basic-literal-1',
		true  === is_trusted(sprintf('test')),
		'sprintf-basic-literal-2',
		true  === is_trusted(sprintf('test %d', $number_1)),
		'sprintf-basic-literal-3',
		true  === is_trusted(sprintf('test %s', $literal_a)),
		'sprintf-basic-non-1',
		false === is_trusted(sprintf('test %s', $non_literal)),

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
				is_trusted($sql),
				is_trusted($in_sql),
				is_trusted($where_sql),
				is_trusted($order_sql),
			);

?>
--EXPECTF--
string(12) "basic-string"
bool(true)
string(9) "basic-int"
bool(true)
string(10) "basic-char"
bool(true)
string(11) "basic-blank"
bool(true)
string(10) "basic-null"
bool(true)
string(16) "basic-var-string"
bool(true)
string(13) "basic-var-int"
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
string(17) "value-null-direct"
bool(true)
string(19) "value-null-variable"
bool(true)
string(29) "value-number-direct-boolean-1"
bool(true)
string(29) "value-number-direct-boolean-1"
bool(true)
string(27) "value-number-direct-float-1"
bool(true)
string(27) "value-number-direct-float-2"
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
string(23) "concat-append-literal-1"
bool(true)
string(23) "concat-append-literal-2"
bool(true)
string(25) "concat-append-non-1-start"
bool(true)
string(23) "concat-append-non-2-end"
bool(true)
string(26) "concat-append-non-3-middle"
bool(true)
string(17) "concat-append-non"
bool(true)
string(17) "concat-append-int"
bool(true)
string(23) "concat-append-int-var-1"
bool(true)
string(23) "concat-append-int-var-2"
bool(true)
string(18) "concat-append-null"
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
string(25) "concat-str_repeat-literal"
bool(true)
string(21) "concat-str_repeat-non"
bool(true)
string(22) "concat-str_pad-literal"
bool(true)
string(18) "concat-str_pad-non"
bool(true)
string(22) "concat-implode-literal"
bool(true)
string(19) "concat-implode-none"
bool(true)
string(31) "concat-array_pad-source-literal"
bool(true)
string(30) "concat-array_pad-value-literal"
bool(true)
string(28) "concat-array_pad-value-non-1"
bool(true)
string(28) "concat-array_pad-value-non-2"
bool(true)
string(33) "concat-array_pad-source-literal-1"
bool(true)
string(33) "concat-array_pad-source-literal-2"
bool(true)
string(33) "concat-array_pad-source-literal-3"
bool(true)
string(31) "concat-array_fill-value-literal"
bool(true)
string(27) "concat-array_fill-value-non"
bool(true)
string(23) "sprintf-basic-literal-1"
bool(true)
string(23) "sprintf-basic-literal-2"
bool(true)
string(23) "sprintf-basic-literal-3"
bool(true)
string(19) "sprintf-basic-non-1"
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