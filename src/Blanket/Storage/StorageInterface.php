<?php

namespace Blanket\Storage;

/**
 * Interface StorageInterface.
 *
 * @package Blanket
 */
interface StorageInterface {

  /**
   * Sets the schema registry in order to later bind placeholder types.
   *
   * @param array $registry
   *   Array, keyed by column name, of arrays with `name` and `type`.
   */
  public function setSchemaRegistry($registry);

  /**
   * Last inserted ID from most recent insert.
   *
   * @return int
   */
  public function lastInsertId();

  /**
   * Factory for INSERT statement.
   *
   * @param string $table
   *   Name of table to query.
   *
   * @return InsertStatementInterface
   *   Insert statement.
   */
  public function insert($table);

  /**
   * Factory for UPDATE statement.
   *
   * @param string $table
   *   Name of table to query.
   *
   * @return UpdateStatementInterface
   *   Update statement.
   */
  public function update($table);

  /**
   * Factory for DELETE statement.
   *
   * @param string $table
   *   Name of table to query.
   *
   * @return DeleteStatementInterface
   *   Delete statement.
   */
  public function delete($table);

  /**
   * Factory for SELECT statement.
   *
   * @param string $table
   *   Name of table to query.
   *
   * @return SelectStatementInterface
   *   Select statement.
   */
  public function select($table);

}