<?php

namespace Blanket\Db;

use Blanket\Storage\SelectStatementInterface;

/**
 * Class DbSelectStatement.
 *
 * @package Blanket
 */
class DbSelectStatement extends AbstractDbStatement implements SelectStatementInterface {

  /**
   * SQL limit string.
   *
   * @var string
   */
  private $limit_string = '';

  /**
   * Set bounds on limit statement.
   *
   * @param int $start
   *   Limit start.
   *
   * @param int $count
   *   Count number
   *
   * @return static
   *   Self.
   */
  public function range($start, $count) {
    $this->limit_string = sprintf('LIMIT %d, %d', $start, $count);

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  protected function getSqlStatement() {
    return trim(strtr('SELECT * FROM ___TABLE___ ___CONDITIONS___ ___LIMIT___', [
      '___TABLE___' => $this->table,
      '___CONDITIONS___' => $this->getConditionsString(),
      '___LIMIT___' => $this->limit_string,
    ]));
  }

  /**
   * Executes and fetches all results.
   *
   * @return array
   *   Results as associative arrays.
   */
  public function executeAndFetchAll() {
    return $this->execute() ? $this->statement->fetchAll(\PDO::FETCH_ASSOC) : [];
  }

}