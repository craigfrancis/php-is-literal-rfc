<?php

declare(strict_types=1);

class Value {
	private ?string $value = NULL;
	public function __construct(string|int|float $value) {
		$this->value = strval($value);  // Use parameterised SQL
	}
	public function value_get(): ?string {
		return $this->value;
	}
	public function __toString(): string {
		return '?';
	}
}

class Field {
	private ?string $value = NULL;
	private ?Field $as = NULL;
	public function __construct(string $value, string $as = NULL) {
		$this->value = $value;
		if ($as !== NULL) {
			$this->as_set($as);
		}
	}
	public function as_set(string $as): self {
		$this->as = new Field($as);
		return $this;
	}
	public function __toString(): string {
		return '`' . str_replace('`', '``', (string)$this->value) . '`' . ($this->as ? ' AS ' . $this->as : '');
	}
}

class Table extends Field {
}

class Func {
	private ?string $name = NULL;
	private ?Field $as = NULL;
	/**
	 * @var array<int|string, string|Field|Value|SubQuery|Calc> $args
	 */
	private array $args = [];
	/**
	 * @param string|Field|Value|SubQuery|Calc $args
	 */
	public function __construct(string $name, ...$args) {
		if (!in_array($name, ['COUNT', 'SUM', 'IF', 'IFNULL', 'LEFT', 'LENGTH'])) {
			throw new RuntimeException('Invalid statement type');
		}
		$this->name = $name;
		$this->args = $args;
	}
	public function as_set(string $as): self {
		$this->as = new Field($as);
		return $this;
	}
	public function __toString(): string {
		$sql = [];
		if ($this->name == 'COUNT' && count($this->args) === 1 && reset($this->args) === '*') {
			$sql[] = '*';
		} else {
			foreach ($this->args as $arg) {
				if (!($arg instanceof Field) && !($arg instanceof Value) && !($arg instanceof SubQuery) && !($arg instanceof Calc)) {
					$arg = new Field($arg);
				}
				$sql[] = $arg->__toString();
			}
		}
		return strval($this->name) . '(' . implode(', ', $sql) . ')' . ($this->as ? ' AS ' . $this->as : '');
	}
}

class Calc {
	/**
	 * @var array<int|string, string|Field|Value|SubQuery|Func> $args
	 */
	private array $args = [];
	/**
	 * @param string|Field|Value|SubQuery|Func $args
	 */
	public function __construct(...$args) {
		$this->args = $args;
	}
	public function __toString(): string {
		$sql = [];
		$k = 0;
		foreach ($this->args as $arg) {
			if ($k++ % 2) {
				if (!in_array($arg, ['+', '-'])) {
					throw new RuntimeException('Invalid calculation operator');
				}
				$sql[] = $arg;
			} else {
				if (!($arg instanceof Field) && !($arg instanceof Value) && !($arg instanceof SubQuery) && !($arg instanceof Func)) {
					$arg = new Field($arg);
				}
				$sql[] = $arg->__toString();
			}
		}
		return '(' . implode(' ', $sql) . ')';
	}
}

class Statement {
	protected ?string $type = NULL;
	/**
	 * @var array<int|string, string|Field|Func|Calc|SubQuery|Statement> $fields
	 */
	protected array $fields = [];
	protected Field|Table|null $from = NULL;
	/**
	 * @var array<int|string, string|Field|Func|Calc|SubQuery|Statement> $where
	 */
	protected array $where = [];
	public function __construct(string $type) {
		if (!in_array($type, ['SELECT', 'INSERT', 'UPDATE', 'DELETE'])) {
			throw new RuntimeException('Invalid statement type');
		}
		$this->type = $type;
	}
	public function field_add(string|Field|Func|Calc|SubQuery|Statement $field, string $as = NULL): self {
		if (!($field instanceof Field) && !($field instanceof Func) && !($field instanceof Calc) && !($field instanceof SubQuery) && !($field instanceof Statement)) {
			$field = new Field($field);
		}
		if ($as !== NULL) {
			$as = new Field($as);
			$as = ' AS ' . $as;
		}
		$this->fields[] = $field . ($as ?? '');
		return $this;
	}
	/**
	 * @param string|Field|Func|Calc|SubQuery|Statement $fields
	 */
	public function fields_set(...$fields): self {
		foreach ($fields as $id => $field) {
			if (!($field instanceof Field) && !($field instanceof Func) && !($field instanceof Calc) && !($field instanceof SubQuery) && !($field instanceof Statement)) {
				$fields[$id] = new Field($field);
			}
		}
		$this->fields = $fields;
		return $this;
	}
	public function from_set(string|Table $table): self {
		if (!($table instanceof Table)) {
			$table = new Field($table);
		}
		$this->from = $table;
		return $this;
	}
	public function where_add(string|Field|Func|Calc|SubQuery $field, ?string $comparison = NULL, string|int|float|null|Field|Value $value = NULL): self {
		if (!($field instanceof Field) && !($field instanceof Func) && !($field instanceof Calc) && !($field instanceof SubQuery)) {
			$field = new Field($field);
		}
		$sql = $field;
		if ($comparison) {
			if (in_array($comparison, ['=', '!=', '>', 'LIKE', 'REGEXP', 'IS NULL'])) {
				$sql .= ' ' . $comparison;
			} else {
				throw new RuntimeException('Invalid where comparison');
			}
		}
		if ($value instanceof Field) {
			$sql .= ' ' . $value->__toString();
		} else if ($value) {
			$sql .= ' ?';
		}
		$this->where[] = $sql;
		return $this;
	}
	public function __toString(): string {
		return strval($this->type) . ' ' . implode(', ', $this->fields) . ' FROM ' . strval($this->from) . ' WHERE ' . implode(' AND ', $this->where);
	}
	public function execute(): self {
		echo $this->__toString() . ";\n";
		return $this;
	}
}

class SubQuery extends Statement {
	protected ?Field $as = NULL;
	public function __construct() {
		$this->type = 'SELECT';
	}
	public function as_set(string $as): self {
		$this->as = new Field($as);
		return $this;
	}
	public function __toString(): string {
		return '(' . parent::__toString() . ')' . ($this->as ? ' AS ' . $this->as : '');
	}
}

//--------------------------------------------------

// function Value(...$args):     Value     { return new Value(...$args); }
// function Field(...$args):     Field     { return new Field(...$args); }
// function Table(...$args):     Table     { return new Table(...$args); }
// function Func(...$args):      Func      { return new Func(...$args); }
// function Calc(...$args):      Calc      { return new Calc(...$args); }
// function Statement(...$args): Statement { return new Statement(...$args); }
// function SubQuery(...$args):  SubQuery  { return new SubQuery(...$args); }

//--------------------------------------------------

// LEFT(ref, (LENGTH(ref) - 1))

// SELECT
// 	`id`,
// 	`ref`,
// 	IFNULL(`type`, "N/A") AS type,
// 	LEFT(`ref`, (LENGTH(`ref`) - 3)) AS `ref_short`,
// 	(
// 		SELECT
// 			COUNT(*)
// 		FROM
// 			`assessment_file`
// 		WHERE
// 			`assessment_id` = `id`
// 	) AS `file_count`
// FROM
// 	`assessment`
// WHERE
// 	`id` = ? AND
// 	`deleted` IS NULL;

//--------------------------------------------------

$id = 1;

$statement = (new Statement('SELECT'))
	->fields_set(
		'id',
		'ref',
		(new Func('IFNULL', 'type', new Value('N/A')))->as_set('type'),
		(new Func('LEFT', 'ref', new Calc(new Func('LENGTH', 'ref'), '-', new Value(3))))->as_set('ref_short'),
	)
	->from_set('assessment')
	->where_add('id', '=', $id)
	->where_add('deleted', 'IS NULL');

$statement->execute();

//--------------------------------------------------

$id = 1;

$statement = (new Statement('SELECT'))
	->field_add('id')
	->field_add('ref')
	->field_add(new Func('IFNULL', 'type', new Value('N/A')), 'type')
	->field_add(new Func('LEFT', 'ref', new Calc(new Func('LENGTH', 'ref'), '-', new Value(3))), 'ref_short')
	->field_add(
		(new SubQuery())
		->field_add(new Func('COUNT', '*'))
		->from_set('assessment_file')
		->where_add('assessment_id', '=', new Field('id')),
		'file_count'
	)
	->from_set('assessment')
	->where_add('id', '=', $id)
	->where_add('deleted', 'IS NULL');

$statement->execute();
