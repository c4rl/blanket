<?php

namespace Blanket\Storage;

/**
 * Interface SelectStatementInterface.
 *
 * @package Blanket
 */
interface SelectStatementInterface extends StorageStatementInterface {

  /**
   * Set bounds on limit statement.
   *
   * @param int $start
   *   Limit start.
   * @param int $count
   *   Count number
   *
   * @return static
   *   Self.
   */
  public function range($start, $count);

  /**
   * Executes and fetches all results.
   *
   * @return array
   *   Results as associative arrays.
   */
  public function executeAndFetchAll();

}
