<?php

namespace Blanket;

/**
 * Class Request.
 *
 * @package Blanket
 */
class Request {

  /**
   * Path of request.
   *
   * @var string.
   */
  public $path;

  /**
   * HTTP method, lowercase.
   *
   * @var string
   */
  public $method;

  /**
   * Key-values from POST request.
   *
   * @var array
   */
  public $post_data;

  /**
   * Key-values from GET request.
   *
   * @var array
   */
  public $get_data;

  /**
   * Key-values from PUT request.
   *
   * @var array
   */
  public $put_data;

  /**
   * Protocol of server, $_SERVER['SERVER_PROTOCOL'].
   *
   * @var string
   */
  public $protocol;

  /**
   * Reads raw JSON from PUT data into array.
   *
   * @return array
   *   Key-value of PUT data.
   */
  private static function readInputData() {
    $h = fopen('php://input', 'r');
    $json = '';
    while ($chunk = fread($h, 1024)) {
      $json .= $chunk;
    }
    fclose($h);

    return json_decode($json, TRUE);
  }

  /**
   * @param array $globals
   *   Optional globals for construction.
   *
   * @return Request
   *   Created instance.
   */
  public static function createFromGlobals($globals = NULL) {
    if (!isset($globals)) {
      $globals = $_SERVER + [
        '_GET_DATA' => $_GET,
        '_POST_DATA' => NULL,
        '_PUT_DATA' => NULL,
      ];

      switch ($globals['REQUEST_METHOD']) {
        case 'POST':
          $globals['_POST_DATA'] = preg_match('/application\/x-www-form-urlencoded/', $_SERVER['CONTENT_TYPE']) ? $_POST : self::readInputData();
          break;

        case 'PUT':
          $globals['_PUT_DATA'] = $globals['REQUEST_METHOD'] == 'PUT' ? self::readInputData() : NULL;
          break;
      }
    }

    $instance = new self();
    $instance->method = strtolower($globals['REQUEST_METHOD']);
    $instance->path = trim(parse_url($globals['REQUEST_URI'])['path'], '/');
    $instance->post_data = $globals['_POST_DATA'];
    $instance->get_data = $globals['_GET_DATA'];
    $instance->put_data = $globals['_PUT_DATA'];
    $instance->protocol = $globals['SERVER_PROTOCOL'];

    return $instance;
  }

}
