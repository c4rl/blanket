<?php

namespace Blanket;

/**
 * Class Utility.
 *
 * General utilities and helpers.
 *
 * @package Blanket
 */
class Utility {

  /**
   * Convert a string to snake case.
   *
   * @param string $value
   *   String to convert
   * @param string $delimiter
   *   Delimiter, usually underscore.
   *
   * @return string
   *   A snake_case_string.
   */
  public static function snakeCase($value, $delimiter = '_') {
    static $cache = [];
    $key = $value.$delimiter;
    if (isset($cache[$key])) {
      return $cache[$key];
    }
    if (!ctype_lower($value)) {
      $value = preg_replace('/\s+/', '', $value);
      $value = strtolower(preg_replace('/(.)(?=[A-Z])/', '$1'.$delimiter, $value));
    }
    return $cache[$key] = $value;
  }

  /**
   * Convert a value to camel case.
   *
   * @param string $value
   *  String to convert.
   *
   * @return string
   *  A camelCaseString.
   */
  public static function camelCase($value) {
    static $cache = [];
    if (isset($cache[$value])) {
      return $cache[$value];
    }
    return $cache[$value] = lcfirst(self::studlyCase($value));
  }

  /**
   * Convert a value to studly caps case.
   *
   * @param string $value
   *  String to convert.
   *
   * @return string
   *  A StudlyCaseString.
   */
  public static function studlyCase($value) {
    static $cache = [];
    if (isset($cache[$value])) {
      return $cache[$value];
    }
    return $cache[$value] = str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', $value)));
  }

}
