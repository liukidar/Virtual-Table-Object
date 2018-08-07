# Virtual-Table-Object (VTO)
A collection of objects to virtualize the tables of a MySQL database into a navigable object.

A VTO maps a table into a php object describing its fields and their relationships with other tables: each field can lead, with a custom condition, to one (and only one) parent row in another table or many children.

The Virtual-Table-Manager manages all the VTOs and allow the user to query them. Each query will be generated on the fly and then cached into the SESSION (if a query id is provided). Params and variables provided on each call will be substituted each time.

Each VTO must be declared in its own file (inside the 'vtos' directory), named 'VTOClassname.php'. Here's a class template:

```php
//VTOExample.php

class VTOExample extends VTO
{
	public function __construct() 
	{
		parent::__construct('example_id', '_database_table_name', [
			'parent1_id' => ['field_from', 'parent1_table_id', 'field_to'], //e.g. 'parent' => ['parent', 'parent_table_id', 'ID']
			'parent2_id' => ['field_from', 'parent2_table_id', 'field_to'],
			'parent3_id' => ['field_from', 'parent3_table_id', 'filed_to']
		], [
			'child1_id' => ['child1_table_id', link_condition] //e.g. 'example_id.child > child1_table_id.ID'
		]);
	}
}
```

On each VTO table can be performed one of the following actions, making this little library perfect to create a REST api. Each request will be translated into one and only one query.
Variables can be bounded to a query, in the form of :variable_name, and then passed to the query as the array 'vars'; selected field names and chains (with and without '.' are reserved variable names).

# GET
Perform a SELECT, returning a Resource object.
Each field can be a chain of parent, child, ... , value. Aggregator functions can be used on the fields: sum(), concat().

Example:

```php

$res = $vtm->get('vto_id.query_id', [
	'fields' => ['field1', 'child_filed1.value', sum('child_field2.price'), 'parent_field1.tyepology'],
	'where' => 'field2 = ? AND field3 = ?',
	'params' => [8553, 'small']
]);

while($r = $res->next()) {
	//$r is a php array
}

```

# PUT
Perform a UPDATE, returning the number of affected rows.
Own and child fields can be updated, parent's ones cannot: trying to update a parent field will instead act as a select and the result row will be used to link the child to this new parent, if no parents exists the query won't update anything.

```php

$res = $vtm->put('vto_id.query_id', [ 
	'fields' => ['field1' => 'value_to_update1', 'child_field1.field2' => 'value_to_update2', 'parent_field1.field3' => 'value_to_update3', 'parent_field1.child_field2.field4' => 'value_to_update4'],
	'where' => 'field2 = ?',
	'params' => [8553]
]);
	
```

# POST - NOT IMPLEMENTED


# DELETE - NOT IMPLEMENTED

