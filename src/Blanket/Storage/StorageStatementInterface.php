<?php

namespace Blanket\Storage;

/**
 * Interface StorageStatementInterface
 *
 * @package Blanket
 */
interface StorageStatementInterface  {

  /**
   * Provides a condition for the statement.
   *
   * @param string $key
   *   Name of column.
   * @param mixed $value
   *   Value to match.
   * @param string $operator
   *   Comparison operator, defaults to '<'.
   *
   * @return $this
   *   Self.
   */
  public function condition($key, $value, $operator = '=');

  /**
   * Executes statement.
   *
   * @return bool
   *   TRUE on success, FALSE otherwise.
   */
  public function execute();

  /**
   * Returns schema array for table.
   *
   * @return array
   *   Schema array keyed by field with 'name' and 'type'.
   */
  public function getSchema();

  /**
   * Fluent setter for fields applicable to query.
   *
   * @param array $fields
   *   Key-value of fields.
   *
   * @return $this
   *   Self.
   */
  public function fields(array $fields);

}
