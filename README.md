# Virtual-Table-Object (VTO)
*A collection of objects to virtualize the tables of a MySQL database into a navigable object.*

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
			'child1_id' => ['child1_table_id', ['get-on' => condition1, 'update-on' => condition2, 'delete-on' => condition3] //e.g. 'example_id.child > child1_table_id.ID', get-on condition is required and it's the default value for the other two ones 
		]);
	}
}

```

On a VTO can be performed one of the following actions, making this little library perfect to create a REST api. Each request will be translated into one and only one query.
Variables can be bounded to a query, in the form of :variable_name and then passed to the query through the array 'params'; selected field names and chains (with and without '.' are reserved variable names).

## GET
Perform a SELECT, returning a Resource object.
Each field can be a chain of parent, child, ... , value. Aggregator functions can be used on the fields: sum(), concat(). Options can be added as a MySQL string.

Example:

```php

$res = $vtm->get('vto_id.query_id', [
	'fields' => ['field1', 'child_filed1.value', VTA::sum('child_field2.price'), 'parent_field1.typology'],
	'where' => 'vto_id.field2 = ? AND vto_id.field3 = ?',
	'params' => [1, 'true'],
	'options' => 'LIMIT 2 OFFSET 3'
]);

while($r = $res->next()) {
	//$r is a php array
}

```

## PUT
Perform a UPDATE, returning the number of affected rows.
Own and child fields can be updated, parent's ones cannot: trying to update a parent field will instead act as a select and the result row will be used to link the child to this new parent, if no parent exists the query won't update anything.

```php

$res = $vtm->put('vto_id.query_id', [ 
	'fields' => ['field1' => 'value_to_update1', 'child_field1.field2' => 'value_to_update2', 'parent_field1.field3' => 'value_to_update3', 'parent_field1.child_field2.field4' => 'value_to_update4'],
	'where' => 'vto_id.field2 = ?',
	'params' => [5]
]);

```

## POST
Perform a INSERT, returning 1 or 2 if the element is inserted or updated, FALSE if already present (and not updated, like with ID = ID).
Inserting a parent field has the same behaviour of PUT. Inserting a child field has undefined behaviour (does not make sense to me), probably the query won't be valid MySQL, but you can try. The 'ON DUPLICATE KEY UPDATE' behaviour can be configured through the 'duplicate' param: you can either pass 'update' and the element will be updated with the POST data or a custom string, with each field prefixed by 'vto_id.'. Options can be added.

```php

$res = $vtm->post('vto_id.query_id', [ 
	'fields' => ['field1' => 'value_to_insert1', 'parent_field1.field2' => 'value_to_insert2', 'parent_field2.field3' => 'value_to_insert3']
	'duplicate' => 'vto_id.ID = vto_id.ID' //or => 'update'
]);

```

## DELETE
Perform a DELETE, returning the number of affected rows. Works exactly like a GET but each 'field' is actually a 'table' (or a chain of parent, child, ... tables). Options can be added.

```php

$res = $vtm->delete('vto_id.query_id', [ 
	'fields' => ['child_table1', 'parent_table1', 'parent_table2.child_table2']
	'where' => 'vto_id.field2 = ?',
	'params' => [8]
]);

```

### ADVANCED & INFO
* You can avoid prefixing 'vto_id' to fields in the 'where', 'duplicate', 'options' clauses if it's not ambiguuos (when the main table is never joined as a child or parent).
* For advanced options you can refer to secondary tables' fields in the 'where', 'duplicate', 'options' clauses: their prefix (vto_id) is their own path without the '.' (dot) character. *E.g. child1.parent1.parent2.ID => child1parent1parent2.ID*
* You can disable caching by passing 'false' to the VTM constructor or by not giving an ID to the queries. *E.g $vtm->delete('vto_id', ...)*
* You can change the 'vtos' directory path by passing the new relative path to the VTM constructor.
* The aggregators are declared inside the VTA class as static methods.
* get-on, update-on, delete-on condition can be provided to a parent link as fourth parameters. The result join condition will be: field_from = field_to AND custom condition.
