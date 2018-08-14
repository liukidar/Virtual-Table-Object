<?php

require_once 'VTObject.php';
require_once 'VTParser.php';

/**
 * Manages all the VTOs and provide the main user interface:
 * get, put, post, delete
 */
class VTM
{
  protected $sql;
  protected $VTP;
  protected $VTOs = [];
  protected $path;
  protected $cache;

  protected $exceptionHandler;
  protected $error;
  
  /**
   * @param mixed MySQL query and escaping capable object
   * @param array [path, cache, error]
   */
  public function __construct($_sql, $_options = NULL)
  {
    session_start();
    $this->sql = $_sql;
    $this->VTP = new VTP($this);
    $this->path = $_options['path'] ?: 'vtos/';
    $this->cache = $_options['cache'] ?: false;
    $this->exceptionHandler = $_options['error'] ?: NULL;

    $this->sql->query('SET SESSION group_concat_max_len = 1024 * 1024');
  }

  /**
   * @param string error main description
   * @param string error sensitive data
   */
  public function throw_error($_error, $_info) {
    if($this->exceptionHandler) {
      $this->exceptionHandler->throw_error($_error, $_info);
    } else {
      echo '<b>'.$_error.': '.$_info.'</b><br>';
    }

    $this->error[] = $_error.': '.$_info;
  }

  /**
   * Load the requested VTO, if it's not been already cached
   */
  public function require($_VTO)
  {
    if(!isset($this->VTOs[$_VTO])) {
      $class = 'VTO' . ucfirst($_VTO);
      require_once $this->path . $class . '.php';
      $this->VTOs[$_VTO] = new $class;
    }

    return $this->VTOs[$_VTO];
  }

  /**
   * Prepare the query to be executed
   * @param int query type
   * @param string target table
   * @param array [fields, where, params, options, duplicate]
   */
  public function query($_query, $_VTO, $_data)
  {
    $this->error = 0;

    $cached = strpos($_VTO, '.');
    if($cached !== FALSE) {
      $id = $_VTO;
      $_VTO = substr($_VTO, 0, $cached);
    }
    else {
      $id = NULL;
    }

    $query = '';
    // Creating cached query
    if(!isset($_SESSION['VTM'][$id]) || !$this->cache) {
      $VTO = $this->require($_VTO);
      switch ($_query) {
        case VTOQ_GET:
          $query = $this->VTP->parse_get($VTO, $_data);
          break;
        case VTOQ_PUT:
          $query = $this->VTP->parse_put($VTO, $_data);
          break;
        case VTOQ_POST:
          $query = $this->VTP->parse_post($VTO, $_data);
          break;
        case VTOQ_DELETE:
          $query = $this->VTP->parse_delete($VTO, $_data);
          break;

        default:
          $this->throw_error('Query type not supported', $_query);
          break;
      }
      if($id) {
        $_SESSION['VTM'][$id] = $query;
      }
    } else {
      // Loading query from cache
      $query = $_SESSION['VTM'][$id];
    }
    
    // Bind unamed params to ?
    if(isset($_data['params'])) {
      // Replace each '?' with its param
      $params = &$_data['params'];
      $query[0] = preg_replace_callback( '/\?/', function($match) use(&$params) {
        return  '\''.$this->sql->real_escape_string(array_shift($params)).'\'';
      }, $query[0]);
    }
    // Bind named params to :
    foreach($query[1] as $key => &$var) {
      $key = substr($key, 1);
      $var = $_data['params'][$key] ?: $_data['fields'][$key];
      if(is_array($var)) {
        // [a, b, c, d] => "'a','b','c','d'"
        $var = implode(',', array_walk($var, function($el) {
          return '\''.$this->sql->real_escape_string($el).'\'';
        }));
      }
      else {
        // a => 'a'
        $var = '\''.$this->sql->real_escape_string($var).'\'';
      }
    }
    // Actual variables substitution
    $query = strtr($query[0], $query[1]);
    
    return $query;
  }
  
  /**
   * Perform a SELECT
   * @return Resource selected rows
   */
  public function get($_VTO, $_data)
  {
    $query = $this->query(VTOQ_GET, $_VTO, $_data);
    
    echo $query;

    $res = new Resource($this->sql->query($query));
    if($this->sql->exceptionHandler) {
      $this->throw_error('Invalid query', $this->sql->exceptionHandler);
    }

    return $res;
  }

  /**
   * Perform a UPDATE
   * @return int number of affected rows
   */
  public function put($_VTO, $_data)
  {
    $query = $this->query(VTOQ_PUT, $_VTO, $_data);

    echo $query;

    $this->sql->query($query);
    if($this->sql->exceptionHandler) {
      $this->throw_error('Invalid query', $this->sql->exceptionHandler);
    }

    return $this->sql->affected_rows;
  }

  /**
   * Perform a INSERT
   * @return int number of affected rows according to MySQL specification
   */
  public function post($_VTO, $_data)
  {
    $query = $this->query(VTOQ_POST, $_VTO, $_data);

    $this->sql->query($query);
    if($this->sql->exceptionHandler) {
      $this->throw_error('Invalid query', $this->sql->exceptionHandler);
    }

    return $this->sql->affected_rows;
  }

  /**
   * Perform a DELETE
   * @return int number of deleted rows
   */
  public function delete($_VTO, $_data)
  {
    $query = $this->query(VTOQ_DELETE, $_VTO, $_data);

    $this->sql->query($query);
    if($this->sql->exceptionHandler) {
      $this->throw_error('Invalid query', $this->sql->exceptionHandler);
    }

    return $this->sql->affected_rows;
  }
}