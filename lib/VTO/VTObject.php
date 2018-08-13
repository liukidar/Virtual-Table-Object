<?php

define('VTOL_NONE', 0);
define('VTOL_CHILD', 1);
define('VTOL_PARENT', 2);
define('VTOQ_GET', 0);
define('VTOQ_PUT', 1);

/**
 * Child -> Parent relation to $table on $table.$to_field = $from_field
 * Update condition describes when a new value is valid 
 */
class _Parent
{
  // Foreign key field
  public $from_field;
  // Linked table
  public $table;
  // Linked field
  public $to_field;
  //TODO update condition

  public function __construct($_from_field, $_table, $_to_field)
  {
    $this->from_field = $_from_field;
    $this->table = $_table;
    $this->to_field = $_to_field;
  }
}

/**
 * Parent -> Child relation to $table om $condition
 */
class _Child
{
  public $table;
  public $condition;
  //TODO delete condition

  public function __construct($_table, $_condition)
  {
    $this->table = $_table;
    $this->condition = $_condition;
  }
}

class VTO
{
  public $id;
	public $name;
  public $parents = [];
  public $children = [];

  /**
   * @param _id Name of the VTO
   * @param _table Name of the table in the database
   * @param _parents List of parents
   * @param _children List of children
   */
  public function __construct($_id, $_table, $_parents = [], $_children = [])
  {
    $this->id = $_id;
    $this->name = $_table;
    foreach($_parents as $id => $parent) {
      $this->parents[$id] = new _Parent($parent[0], $parent[1], $parent[2]);
    }
    foreach($_children as $id => $children) {
      $this->children[$id] = new _Child($children[0], $children[1]);
    }
  }
}
