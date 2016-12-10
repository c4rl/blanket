<?php

namespace Blanket;

/**
 * Class Debug.
 *
 * @package Blanket
 */
class Debug {

  /**
   * Captured version of var_dump().
   *
   * @param mixed $var
   *   Variable.
   *
   * @return string
   *   Value of var_dump().
   */
  private static function varDump($var) {
      ob_start();
      var_dump($var);
      $return = ob_get_contents();
      ob_end_clean();
      return $return;
  }

  /**
   * Writes dumped variable to file.
   * @param mixed $var
   *   Variable.
   * @param bool $raw
   *   If TRUE, will return var_dump() value.
   * @param string $target
   *   Path of file to write to.
   */
  private static function writeToFile($var, $raw = FALSE, $target = '/tmp/debug.txt') {
    $value = $raw ? $var : self::varDump($var);
    file_put_contents($target, $value . PHP_EOL, FILE_APPEND);
  }

  /**
   * Debugs var(s) to file.
   *
   * @param mixed
   *   All arguments will be written via writeToFile() with datestamp.
   */
  public static function debug() {
    $vars = func_get_args();
    static $once;
    if (empty($once)) {
      $once = TRUE;
      self::writeToFile(sprintf('### %s ###', date('r')), TRUE);
    }
    foreach ($vars as $var) {
      self::writeToFile($var);
    }
  }

}
