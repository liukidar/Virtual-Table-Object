# Virtual-Table-Object (VTO)
A collection of objects to virtualize the tables of a MySQL database into a navigable object.

A VTO maps a table into a php object describing its fields and their relationships with other tables: each field can lead, with a custom condition, to one (and only one) parent row in another table or many children. On each VTO table can be performed one of the following actions, making this little library perfect to create a REST api.

# GET
Perform a SELECT, returning a Resource object.

# PUT
Perform a UPDATE, returning the number of affected rows.

# POST - NOT IMPLEMENTED

# DELETE - NOT IMPLEMENTED


The Virtual-Table-Manager manages all the VTOs and allow the user to query them. Each VTO must be declared in its own file (inside the 'vtos' directory), named 'VTOClassname.php'. Here's is a class template:

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
