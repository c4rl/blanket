<?php

namespace Blanket;

use Blanket\Exception\RecordNotFoundException;
use Blanket\Storage\StorageInterface;

/**
 * Class Model.
 *
 * @package Blanket
 */
class Model {

  /**
   * Array of attributes.
   *
   * @var array
   */
  protected $attributes = [];

  /**
   * Array of attributes at time of construction.
   *
   * @var array
   */
  protected $original_attributes = [];

  /**
   * Name of table in storage.
   *
   * @var string
   */
  protected static $table;

  /**
   * Storage mechanism.
   *
   * @var StorageInterface
   */
  public static $storage;

  /**
   * Static schema registry.
   *
   * @var array
   */
  private static $schema_registry = [];

  /**
   * Returns table name for model storage.
   *
   * @return string
   *   Table name.
   */
  public static function getTable() {
    return static::$table;
  }

  /**
   * Model constructor.
   *
   * @param array $attributes
   *   Attributes.
   */
  public function __construct(array $attributes = []) {
    // Set each individually to take advantage of custom mutators.
    foreach ($attributes as $key => $value) {
      $this->set($key, $value);
    }
    $this->original_attributes = $this->attributes;
  }

  /**
   * Getter for attribute.
   *
   * @param string $key
   *   Name of attribute.
   *
   * @return mixed
   *   Attribute value.
   */
  public function __get($key) {
    return $this->get($key);
  }

  /**
   * Getter for attribute.
   *
   * @param string $key
   *   Name of attribute.
   *
   * @return mixed
   *   Attribute value.
   */
  protected function get($key) {
    $accessor_method = Utility::camelCase(sprintf('get_%s', $key));
    if (method_exists($this, $accessor_method)) {
      return $this->{$accessor_method}();
    }
    else {
      return array_key_exists($key, $this->attributes) ? $this->attributes[$key] : NULL;
    }
  }

  /**
   * Setter for attribute.
   *
   * @param string $key
   *   Name of attribute.
   * @param mixed $value
   *   Attribute value to set.
   */
  public function __set($key, $value) {
    $this->attributes[$key] = $value;
  }

  /**
   * Sets attribute.
   *
   * @param string $key
   *   Name of attribute.
   * @param mixed $value
   *   Attribute value to set.
   */
  protected function set($key, $value) {
    // Use custom mutator, if defined.
    $mutator_method = Utility::camelCase(sprintf('set_%s', $key));
    if (method_exists($this, $mutator_method)) {
      $this->{$mutator_method}($value);
    }
    else {
      $this->attributes[$key] = $value;
    }
  }

  /**
   * Customer mutator for ID.
   *
   * @param mixed $value
   *   ID value. Will be cast to int if not NULL.
   */
  public function setId($value) {
    $this->attributes['id'] = is_numeric($value) ? (int) $value : NULL;
  }

  /**
   * Existence for attribute.
   *
   * @param string $key
   *
   * @return bool
   *   Whether value exists.
   */
  public function __isset($key) {
    return isset($this->attributes[$key]);
  }

  /**
   * Unset for attribute.
   *
   * @param string $key
   *   Name of attribute.
   */
  public function __unset($key) {
    unset($this->attributes[$key]);
  }

  /**
   * Getter for attributes.
   *
   * @return array
   *   Attributes.
   */
  public function getAttributes() {
    return $this->attributes;
  }

  /**
   * Updates attributes.
   *
   * @param array $attributes
   *   Attributes to update.
   *
   * @return $this
   *   Self.
   */
  public function updateAttributes(array $attributes) {
    foreach ($attributes as $key => $value) {
      $this->set($key, $value);
    }

    return $this;
  }

  /**
   * Whether the model has been altered since construction.
   *
   * @return bool
   *   TRUE if model has changed, FALSE otherwise.
   */
  public function hasChanged() {
    return $this->attributes != $this->original_attributes;
  }

  /**
   * Saves instance.
   *
   * @return $this
   *   Self.
   */
  public function save() {
    if (isset($this->id)) {
      static::$storage->update(static::$table)
        ->fields($this->getAttributes())
        ->condition('id', $this->id)
        ->execute();
    }
    else {
      static::$storage->insert(static::$table)
        ->fields($this->getAttributes())
        ->execute();
      // Update id based on this insert.
      $this->id = static::$storage->lastInsertId();
    }

    return $this;
  }

  /**
   * Saves instance if indeed it has changed.
   *
   * @return $this
   *   Self.
   */
  public function saveIfChanged() {
    if ($this->hasChanged()) {
      $this->save();
    }

    return $this;
  }

  /**
   * Static method for instance creation.
   *
   * @param array $attributes
   *   Attributes.
   *
   * @return static
   *   New, saved instance.
   */
  public static function create(array $attributes) {
    return (new static($attributes))->save();
  }

  /**
   * Deletes instance from storage.
   *
   * @return $this
   *   Self.
   */
  public function delete() {
    if (!isset($this->id)) {
      throw new \LogicException();
    }

    static::$storage->delete(static::$table)
      ->condition('id', $this->id)
      ->execute();

    return $this;
  }

  /**
   * Finds record by id.
   *
   * @param int $id
   *   Primary key identifier.
   *
   * @return static
   *   Found instance.
   *
   * @throws RecordNotFoundException
   *   If record not found.
   */
  public static function findOrFail($id) {

    $rows = static::$storage->select(static::$table)
      ->condition('id', $id)
      ->range(0, 1)
      ->executeAndFetchAll();

    if (!empty($rows)) {
      $instance = new static($rows[0]);
      return $instance;
    }
    throw new RecordNotFoundException();
  }

  /**
   * @param int $page
   *   Page of records. Defaults to 1.
   *
   * @param int $per_page
   *   Number of records per page. Defaults to 10.
   *
   * @return static[]
   *   Loaded instances.
   */
  public static function all($page = 1, $per_page = 10) {

    $start = ($page - 1) * $per_page;

    $rows = static::$storage->select(static::$table)
      ->range($start, $per_page)
      ->executeAndFetchAll();

    $instances = [];
    foreach ($rows as $row) {
      $instances[] = new static($row);
    }

    return $instances;
  }

  /**
   * Registers schema of given model class name.
   *
   * @return array
   *   Schema array keyed by field with 'name' and 'type'.
   */
  public static function parseSchema($class_name) {

    if (!array_key_exists($class_name, self::$schema_registry)) {
      $reflection = new \ReflectionClass($class_name);
      $lines = explode(PHP_EOL, $reflection->getDocComment());

      $schema = array_reduce($lines, function (array $schema, $line) {

        $matches = [];
        if (preg_match('/^\* @property ([^ ]+) ([^ ]+) ?.*$/', trim($line), $matches)) {
          $name = preg_replace('/[^a-z_]/i', '', $matches[2]);
          $type = $matches[1];
          $schema[$name] = compact('name', 'type');
        }

        return $schema;
      }, []);

      self::$schema_registry[$class_name] = $schema;

    }

    return self::$schema_registry[$class_name];
  }

}
