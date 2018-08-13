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
  
  public function __construct($_sql, $_path = 'vtos/', $_cache = true)
  {
    session_start();
    $this->sql = $_sql;
    $this->VTP = new VTP();
    $this->path = $_path;
    $this->cache = $_cache;
    $this->sql->safequery('SET SESSION group_concat_max_len = 1024 * 1024');
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

  public function query($_query, $_VTO, $_data)
  {
    $cached = strpos($_VTO, '.');
    if($cached !== FALSE) {
      $id = $_VTO;
      $_VTO = substr($_VTO, 0, $cached);
    }
    else {
      $id = NULL;
    }

    $query = '';
    //Creating cached query
    if(!isset($_SESSION['VTM'][$id]) || !$this->cache) {
      $VTO = $this->require($_VTO);
      switch ($_query) {
        case VTOQ_GET:
          $query = $this->VTP->parse_get($this, $VTO, $_data);
          break;
        case VTOQ_PUT:
          $query = $this->VTP->parse_put($this, $VTO, $_data);

        default:
          //TODO Error
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
    $query[0] = strtr($query[0], $query[1]);
    
    return $query;
  }
  
  public function get($_VTO, $_data)
  {
    $query = $this->query(VTOQ_GET, $_VTO, $_data);
    
    echo $query[0];

    $res = new Resource($this->sql->query($query[0]));
    if($this->sql->error) {
      echo 'Invalid query<br>';
    }

    return $res;
  }

  public function put($_VTO, $_data)
  {
    $query = $this->query(VTOQ_PUT, $_VTO, $_data);

    echo $query[0];

    $this->sql->query($query[0]);
    if($this->sql->error) {
      echo 'Invalid query<br>';
    }

    return $this->sql->affected_rows;
  }
}