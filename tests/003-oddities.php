<?php

$id = 'id';
$id2 = 'id' . $id . time();

var_dump(is_literal($id)); // false, is 'id' an interned string?
var_dump(is_literal($id2)); // false, is 'id' an interned string?

//--------------------------------------------------

echo "\n";

define('FIELD_NAME1', 'something');
define('FIELD_NAME2', 'id');

var_dump(is_literal(FIELD_NAME1));
var_dump(is_literal(FIELD_NAME2)); // false, hopefully the same thing.

//--------------------------------------------------

echo "\n";

class example {
  protected $db_table_fields;
  function __construct() {
    $this->db_table_fields = [
        'id' => 'id',
        'ref' => ($this->username_login() ? 'username' : 'email'),
      ];
    var_dump(is_literal($this->db_table_fields['id'])); // false... interestingly it can be true if you remove the username_login() check.
  }
  function username_login() {
    return true;
  }
}

$example = new example();

//--------------------------------------------------

echo "\n";

var_dump(is_literal('a'));
var_dump(is_literal('')); // Meh, would anyone care?

?>