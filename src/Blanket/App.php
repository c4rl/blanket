<?php

namespace Blanket;

use Blanket\Exception\Http500Exception;
use Blanket\Exception\MissingRouteException;
use Blanket\Storage\StorageInterface;

/**
 * Class App.
 *
 * @method get($path, \Closure $closure) Registers handler for GET request.
 * @method post($path, \Closure $closure) Registers handler for POST request.
 * @method put($path, \Closure $closure) Registers handler for PUT request.
 * @method delete($path, \Closure $closure) Registers handler for DELETE request.
 * @method options($path, \Closure $closure) Registers handler for OPTIONS request.
 *
 * @package Blanket
 */
class App {

  /**
   * Supported HTTP methods.
   *
   * @var array
   */
  const SUPPORTED_METHODS = [
    'get',
    'post',
    'put',
    'delete',
    'options',
  ];

  /**
   * Key-value storage of configuration.
   *
   * @var array
   */
  private $config = [];

  /**
   * Registry for various routes.
   *
   * @var array
   *   Array of route arrays with 'method', 'path', and 'callback' params.
   */
  private $route_registry = [];

  /**
   * Default configuration for web app.
   *
   * Parameters include:
   *  - exception_map: Key-value array of internal exception class names mapping
   *    to HTTP exceptions classes that should be thrown instead.
   *  - models: Array of FQCNs that should have schema registered with storage.
   *  - storage: Instance of Db.
   *
   * @var array
   *   Default configuration array.
   */
  private $default_config = [
    'exception_map' => [
      \Blanket\Exception\RecordNotFoundException::class => \Blanket\Exception\Http404Exception::class,
      \Blanket\Exception\MissingRouteException::class => \Blanket\Exception\Http404Exception::class,
    ],
    'models' => [],
    'resources' => [],
    'storage' => NULL,
  ];

  /**
   * App constructor.
   *
   * @param array $config
   *   Configuration options.
   */
  public function __construct(array $config = []) {
    $this->config = $config + $this->default_config;
    if (count($this->config['resources']) > 0) {
      $this->registerResources();
    }
    if (isset($this->config['storage'])) {
      $this->registerStorageModels();
    }
    $this->initAccessControl();
  }

  /**
   * Registers basic REST resource.
   */
  private function registerResources() {

    /** @var Model $class_name */
    foreach ($this->config['resources'] as $path => $class_name) {
      if (!in_array($class_name, $this->config['models'])) {
        $this->config['models'][] = $class_name;
      }

      $this->post($path, function (Request $request) use ($class_name) {
        return $class_name::create($request->post_data)->getAttributes();
      });

      $unit_path = sprintf('%s/:id', $path);
      $this->get($unit_path, function ($id, Request $request) use ($class_name) {
        return $class_name::findOrFail($id)->getAttributes();
      });

      $this->put($unit_path, function ($id, Request $request) use ($class_name) {
        return $class_name::findOrFail($id)
          ->updateAttributes($request->put_data)
          ->saveIfChanged()
          ->getAttributes();
      });

      $this->delete($unit_path, function ($id, Request $request) use ($class_name) {
        return $class_name::findOrFail($id)
          ->delete()
          ->getAttributes();
      });

      $this->get($path, function (Request $request) use ($class_name, $path) {
        $page = isset($request->get_data['page']) ? (int) $request->get_data['page'] : 1;
        $per_page = isset($request->get_data['per_page']) ? (int) $request->get_data['per_page'] : 10;
        $instances = $class_name::all($page, $per_page);
        $total = count($instances);
        $all_instance_data = array_map(function (Model $instance) {
          return $instance->getAttributes();
        }, $instances);
        return compact('total', 'page', 'per_page') + [
          $path => $all_instance_data
        ];
      });
    }
  }

  /**
   * Registers models with the storage mechanism.
   */
  private function registerStorageModels() {
    /** @var StorageInterface $storage */
    $storage = $this->config['storage'];
    $schema_registry = [];
    foreach ($this->config['models'] as $class_name) {
      /** @var Model $class_name */
      $schema_registry[$class_name::getTable()] = Model::parseSchema($class_name);
      $class_name::$storage = $storage;
    }
    $storage->setSchemaRegistry($schema_registry);
  }

  /**
   * Caller for specific HTTP methods.
   *
   * @param string $method
   *   Method name, i.e. 'get', 'post', 'put', 'del'.
   *
   * @param array $arguments
   *   Arguments passed to method.
   *
   * @throws \TypeError
   *   If path and callback aren't properly passed.
   *
   * @throws \ArgumentCountError
   *   If path and callback aren't properly passed.
   */
  public function __call($method, array $arguments) {

    if (!in_array($method, self::SUPPORTED_METHODS)) {
      throw new \BadMethodCallException();
    }

    if (count($arguments) != 2) {
      throw new \ArgumentCountError();
    }

    list($path, $callback) = $arguments;

    if (!is_string($path) || !($callback instanceof \Closure)) {
      throw new \TypeError();
    }

    $this->route_registry[] = compact('method', 'path', 'callback');
  }

  /**
   * Converts a path mask to a matching regex.
   *
   * @param string $path_mask
   *   Path mask, e.g. "some/path/:param1/:param2/and/:param3"
   *
   * @return string
   *   Regex string.
   */
  public static function pathMaskToRegex($path_mask) {
    static $cache = [];

    if (!array_key_exists($path_mask, $cache)) {
      if ($path_mask == '*') {
        $regex = '/^.+$/';
      }
      else {
        $regex = sprintf('/^%s$/', preg_replace('/\//', '\/', preg_replace('/:[a-z]+/', '(.+?)', $path_mask)));
      }
      $cache[$path_mask] = $regex;
    }

    return $cache[$path_mask];
  }

  /**
   * Extracts array of param names from path mask.
   *
   * @param string $path_mask
   *   Path mask, e.g. "some/path/:param1/:param2/and/:param3"
   *
   * @return array
   *  Array of path names, e.g. ['param1', 'param2', 'param3'].
   */
  public static function pathMaskToNames($path_mask) {
    $pieces = explode('/', $path_mask);
    return array_reduce($pieces, function (array $names, $piece) {
      return $piece[0] == ':' ? array_merge($names, [
        str_replace(':', ':', $piece),
      ]) : $names;
    }, []);
  }

  /**
   * Whether the given route registrant matches the given request.
   *
   * @param array $route_registrant
   *   The registered route array, includes keys 'path', 'method', 'callback'.
   * @param Request $request
   *   Request.
   *
   * @return bool
   *   TRUE if registrant matches, FALSE otherwise.
   */
  public static function requestMatchesRegistrant(array $route_registrant, Request $request) {
    return $request->method == $route_registrant['method'] && preg_match(self::pathMaskToRegex($route_registrant['path']), $request->path);
  }

  /**
   * Extracted arguments from the given request and registrant.
   *
   * @param array $route_registrant
   *   The registered route array, includes keys 'path', 'method', 'callback'.
   * @param \Blanket\Request $request
   *   Request.
   *
   * @return array
   *   Key-value pairs of given path arguments.
   */
  public static function extractPathArguments(array $route_registrant, Request $request) {
    $matches = [];
    if (preg_match(self::pathMaskToRegex($route_registrant['path']), $request->path, $matches)) {
      array_shift($matches);
      return array_combine(self::pathMaskToNames($route_registrant['path']), $matches);
    }
    else {
      return [];
    }
  }

  /**
   * Response from route registrant callback given request parameters.
   *
   * @param Request $request
   *   Request.
   *
   * @return mixed
   *   Value of registrant callback given request parameters.
   */
  public function getResponse(Request $request) {

    $matched_registrant = array_reduce($this->route_registry, function ($matched_route_registrant, $route_registrant) use ($request) {
      if (!isset($matched_route_registrant) && self::requestMatchesRegistrant($route_registrant, $request)) {
        $matched_route_registrant = $route_registrant + [
          'parsed_params' => self::extractPathArguments($route_registrant, $request),
        ];
      }

      return $matched_route_registrant;
    }, NULL);

    if (!isset($matched_registrant)) {
      throw new MissingRouteException();
    }

    return call_user_func_array($matched_registrant['callback'], array_merge(array_values($matched_registrant['parsed_params']), [
      $request,
    ]));

  }

  /**
   * Runs the app given a request. Will set headers and print response.
   *
   * @param Request $request
   */
  public function run(Request $request) {

    header('Cache-Control: no-cache, no-store, must-revalidate');

    try {
      $response = $this->getResponse($request);

      if (!isset($response)) {
        header('Content-Type: text/html');
      }
      elseif (is_string($response)) {
        header('Content-Type: text/html');
        print $response;
      }
      elseif ($response instanceOf \SimpleXMLElement) {
        header('Content-Type: application/xml');
        print $response->saveXML();
      }
      else {
        header('Content-Type: application/json');
        print json_encode($response, JSON_PRETTY_PRINT);
      }

    }
    catch (\Exception $e) {
      $original_exception_class = get_class($e);
      // If we have remapped the thrown exception type, use that exception.
      if (isset($this->config['exception_map'][$original_exception_class])) {
        $mapped_exception_class = $this->config['exception_map'][$original_exception_class];
        /** @var \Exception $mapped_exception */
        $mapped_exception = new $mapped_exception_class();
      }
      else {
        $mapped_exception = new Http500Exception();
      }

      $exception_header_message = sprintf('%s %s %s', $request->protocol, $mapped_exception->getCode(), $mapped_exception->getMessage());

      header('Content-Type: text/html');
      header($exception_header_message, TRUE, $mapped_exception->getCode());

      print $exception_header_message;
    }

  }

  /**
   * Initialize CORS stuff.
   */
  private function initAccessControl() {
    $app = $this;
    $this->options('*', function(Request $request) use ($app) {
      header(sprintf('Allow: %s', strtoupper(implode(', ', $app::SUPPORTED_METHODS))));
    });

    header(sprintf('Access-Control-Allow-Methods: %s', strtoupper(implode(', ', $app::SUPPORTED_METHODS))));
    header('Access-Control-Allow-Headers: Content-Type');
    if (isset($app->config['allow_origin'])) {
      header(sprintf('Access-Control-Allow-Origin: %s', $app->config['allow_origin']));
    }
  }

}
