<?php

/**
 * Parse all the PHP queries into MySQL strings
 */
class VTP
{
  /**
   * Process a query's field, joining all the needed tables
   */
  protected function parse_field($_VTM, $_VTO, $_field, $_query, &$joined_tables, $_options = NULL)
  {
    // ID of the last included table (last element of the join chain)
    $from_table_id = $_VTO->id;
    // ID of the new included table (element to append to the join chain)
    $to_table_id = NULL;

    // Data
    $last_child_id = $from_table_id;
    $last_parent_to_link = NULL;
    $last_parent_from_link = NULL;
    $first_parent_from_link = NULL;
    $first_parent_to_link = NULL;
    $last_link_type = NULL;
    
    // Compute need JOINs
    for($i = 0; $i < count($_field) - 1; ++$i) {
      // Update 'as' ID of the next joined table
      $to_table_id .= $_field[$i];

      // Check if the table is a child
      $link = $_VTO->children[$_field[$i]];
      if(isset($link)) {
        $first_parent_from_link = NULL;
        $first_parent_to_link = NULL;
        $last_child_id = $to_table_id;
        $last_link_type = VTOL_CHILD;
      }
      // Check if the table is a parent
      else {
        $link = $_VTO->parents[$_field[$i]];
        $last_link_type = VTOL_PARENT;
      }
      // Load table
      $link_VTO = $_VTM->require($link->table);

      // If table is not already joined (from other fields) then join it
      if(!isset($joined_tables[$to_table_id])) {
        // Compute join condition
        $condition = '';
        // Add custom condition
        if(isset($link->condition)) {
          // Replace joined table id with computed to table id and active table id with from table id
          $condition .= '(' . str_replace([$_field[$i].'.', $_VTO->id.'.'], [$to_table_id.'.', $from_table_id.'.'], $link->condition) . ')';
        }
        // If is a parent table then add default join condition: parent.ID_to = child.ID_from
        if($last_link_type === VTOL_PARENT) {
          if(isset($link->condition)) {
            $condition.= ' AND ';
          }
          // child.ID_from
          $last_parent_from_link = $from_table_id . '.' . $link->from_field;
          $first_parent_from_link = $first_parent_from_link ?: $last_parent_from_link;
          // parent.ID_to
          $last_parent_to_link = $to_table_id . '.' . $link->to_field;
          $first_parent_to_link = $first_parent_to_link ?: $last_parent_to_link;
          // parent.ID_to = child.ID_from
          $condition .= $last_parent_to_link . ' = ' . $last_parent_from_link;
        }

        // Join table
        $joined_tables[$to_table_id] = ' INNER JOIN ' . $link_VTO->name . ' AS ' . $to_table_id . ' ON ' . $condition; //TODO option for INNER
      }

      // Update values for next iteration
      $_VTO = $link_VTO;
      $from_table_id = $to_table_id;
    }

    switch ($_query) {
      case VTOQ_GET:
        // SELECT
        // Field is last_joined_table.query_field
        $field = $to_table_id . '.' . $_field[$i];
        break;
      
      case VTOQ_PUT:
        // UPDATE
        // If last_joined_table is a parent then you want to select one of its element based on the query_field, and update the query_field with the field value of the
        // first parent table after the last child table in the join chain
        if($last_link_type === VTOL_PARENT) {
          // Field to update = link field of the first parent table
          // last_child_table.first_link_from = first_link_to 
          $field = [$first_parent_from_link, $first_parent_to_link]; // ??? was [$last_child_id . '.' . $first_parent_from_link, $last_parent_to_link]
          // Last table joined needs to be joined on last_parent_table.query_field = query_var
          // instead of last_parent_table.link_to = link_from (2 replaces)
          $joined_tables[$to_table_id] = str_replace([$last_parent_from_link, '.' . $link->to_field], [':' . $_options['as'], '.' . $_field[$i]], $joined_tables[$to_table_id]);
        }
        // If last_joined_table is a child then just update the selected field
        else {
          // last_joined_table.query_field = query_var
          $field = [$to_table_id . '.' . $_field[$i], ':' . $_options['as']];
        }
        break;
    }

    return $field;
  }

  /**
   * Perfrom a select onto the virtual table
   */
  public function parse_get($_VTM, $_VTO, $_data)
  {
    //Array of needed joins
    $joined_tables = [];
    //SELECT
    $query = 'SELECT ';
    foreach($_data['fields'] as $key => $value) {
      // Decode field: "field", [field, name], field => name
      if(!is_integer($key)) {
        $field = $key;
        $as = $value;
      } elseif(is_array($value)) {
        $field = $value[0];
        $as = $value[0];
      }
      else {
        $field = $value;
        $as = $value;
      }

      // Get field path (match: something.else.more_and_more)
      $match = [];
      preg_match('/[a-zA-Z0-9_]+(\.[a-zA-Z0-9_]+)*/m', $field, $match);
      $path = explode('.', $match[0]);
      // Parse field
      $normalized_field = $this->parse_field($_VTM, $_VTO, $path, VTOQ_GET, $joined_tables);
      $field = str_replace($match[0], $normalized_field, $field);

      // Add aggregator
      if(is_array($value)){
        $field = $value[1] . $field . $value[2];
      }

      // Add field to query
      $query.= $field . ' AS \'' . $as . '\', ';
    }
    $query = substr($query, 0, -2);
    
    //FROM
    $query.= ' FROM ' . $_VTO->name . ' AS ' . $_VTO->id;
    
    //JOIN
    foreach($joined_tables as $join) {
      // Join each needed table
      $query.= $join;
    }
    
    //WHERE
    if(isset($_data['where'])){
      // Add where clause
      $query.= ' WHERE ' . $_data['where'];
    }

    //OPTIONS
    if(isset($_data['options'])) {
      // Add option clause
      $query.= ' ' . $_data['options'];
    }

    // Caching all vars to be replaced (match: :somethi.ng_)
    $matches = [];
    preg_match_all('/\:[a-zA-Z0-9_\.]+/m', $query, $matches);
    $vars = [];
    foreach($matches[0] as $var) {
      $vars[$var] = NULL;
    }
    
    return [$query, $vars];
  }
  
  public function parse_put($_VTM, $_VTO, $_data)
  {
    $joined_tables = [];
    $normalized_fields = [];
    //UPDATE
    $query = 'UPDATE ' . $_VTO->name . ' AS ' . $_VTO->id;
    foreach($_data['fields'] as $key => $field) {
      if(!is_int($key)){
        $field = $key;
      }
      // Find linked field
      preg_match('/([a-zA-Z0-9_]+)((\.)([a-zA-Z0-9_]+))*/m', $field, $match);
      $path = explode('.', $match[0]);
      // Parse field
      $normalized_field = $this->parse_field($_VTM, $_VTO, $path, VTOQ_PUT, $joined_tables, ['as' => $field]);
      $normalized_fields[$field] = [str_replace($match[0], $normalized_field[0], $field), $normalized_field[1]];
    }

    //JOIN
    foreach($joined_tables as $join) {
      // Join each needed table
      $query.= $join;
    }
      
    $query.= ' SET ';
    foreach($_data['fields'] as $key => $field) {
      if(!is_int($key)){
        $field = $key;
      }
      // Add field update
      $query.= $normalized_fields[$field][0] . ' = '. $normalized_fields[$field][1] . ', ';
    }
    $query = substr($query, 0, -2);

    //WHERE
    if(isset($_data['where'])){
      $query.= ' WHERE ' . $_data['where'];
    }

      // Cache all vars to be replaced (match: :somethi.ng_)
    $matches = [];
    preg_match_all('/\:[a-zA-Z0-9_\.]+/m', $query, $matches);
    $vars = [];
    foreach($matches[0] as $var) {
      $vars[$var] = NULL;
    }
    
    return [$query, $vars];
  }
}