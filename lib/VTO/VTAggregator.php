<?php

/**
 * MySQL aggregators
 */
class VTA {
  public static function concat($field)
  {
    return [$field, "CONCAT('[\'', GROUP_CONCAT(DISTINCT ", " SEPARATOR '\',\''), '\']')"];
  }

  public static function sum($field)
  {
    return [$field, 'SUM(', ')'];
  }
}