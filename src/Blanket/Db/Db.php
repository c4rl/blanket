<?php

namespace Blanket\Db;

use Blanket\Storage\StorageInterface;

/**
 * Class Db.
 *
 * Used for accessing PDO-based storage.
 *
 * @package Blanket
 */
class Db implements StorageInterface {

  /**
   * Wrapped PDO instance.
   *
   * @var \PDO
   */
  private $pdo;

  /**
   * Cached registry of schema for applicable models.
   *
   * @var array
   */
  private $schema_registry = [];

  /**
   * Db constructor. Works mostly with sqlite for now.
   *
   * @param string $dsn
   *   Data source name.
   */
  public function __construct($dsn) {
    $this->pdo = new \PDO($dsn);
    // We want exceptions thrown pls.
    $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
    $this->pdo->setAttribute(\PDO::ATTR_STRINGIFY_FETCHES, FALSE);
  }

  /**
   * Returns schema array for given table name.
   *
   * @param string $table
   *   Name of table.
   *
   * @return array
   *   Schema array keyed by field with 'name' and 'type'.
   */
  public function getSchema($table) {
    return $this->schema_registry[$table];
  }

  /**
   * Sets the schema registry in order to later bind placeholder types.
   *
   * @param array $registry
   *   Array, keyed by column name, of arrays with `name` and `type`.
   */
  public function setSchemaRegistry($registry) {
    $this->schema_registry = $registry;
  }

  /**
   * Performs static query.
   *
   * @param string $sql
   *   Raw SQL.
   *
   * @return \PDOStatement
   *   Resulting statement.
   */
  public function query($sql) {
    return $this->pdo->query($sql);
  }

  /**
   * Prepares SQL for execution with placeholders.
   *
   * @param string $sql
   *   SQL with placeholders.
   *
   * @return \PDOStatement
   *   Resulting statement.
   */
  public function prepare($sql) {
    return $this->pdo->prepare($sql);
  }

  /**
   * Last inserted ID from most recent query.
   *
   * @return int
   */
  public function lastInsertId() {
    return (int) $this->pdo->lastInsertId();
  }

  /**
   * Factory for INSERT statement query.
   *
   * @param string $table
   *   Name of table to query.
   *
   * @return DbInsertStatement
   *   Insert statement.
   */
  public function insert($table) {
    return new DbInsertStatement($table, $this);
  }

  /**
   * Factory for UPDATE statement query.
   *
   * @param string $table
   *   Name of table to query.
   *
   * @return DbUpdateStatement
   *   Update statement.
   */
  public function update($table) {
    return new DbUpdateStatement($table, $this);
  }

  /**
   * Factory for DELETE statement query.
   *
   * @param string $table
   *   Name of table to query.
   *
   * @return DbDeleteStatement
   *   Delete statement.
   */
  public function delete($table) {
    return new DbDeleteStatement($table, $this);
  }

  /**
   * Factory for SELECT statement query.
   *
   * @param string $table
   *   Name of table to query.
   *
   * @return DbSelectStatement
   *   Select statement.
   */
  public function select($table) {
    return new DbSelectStatement($table, $this);
  }

  /**
   * Coerces attributes to specified types.
   *
   * @param array $attributes
   *   Attributes.
   * @param array $schema
   *   Schema definition.
   *
   * @return array
   *   Coerced attributes.
   */
  public static function coerceAttributes(array $attributes, array $schema) {
    $coerced_attributes = [];
    foreach ($attributes as $name => $value) {
      $coerced_attributes[$name] = self::coerceType($name, $value, $schema);
    }
    return $coerced_attributes;
  }

  /**
   * Coerces variable to specified type.
   *
   * @param string $name
   *   Name of column.
   * @param mixed $value
   *   Value of column.
   * @param array $schema
   *   Schema definition.
   *
   * @return mixed
   *   Coerced value.
   */
  public static function coerceType($name, $value, array $schema) {
    switch ($schema[$name]['type']) {
      case 'bool':
        $coerced_value = (bool) $value;
        break;

      case 'int':
        $coerced_value = (int) $value;
        break;

      case 'string':
        $coerced_value = (string) $value;
        break;

      default:
        throw new \DomainException();
    }

    return $coerced_value;
  }

}
