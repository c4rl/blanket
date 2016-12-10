<?php

namespace Blanket\Db;

use Blanket\Storage\InsertStatementInterface;

/**
 * Class DbInsertStatement.
 *
 * @package Blanket
 */
class DbInsertStatement extends AbstractDbStatement implements InsertStatementInterface {

  /**
   * {@inheritdoc}
   */
  protected function getSqlStatement() {

    $name_pieces = [];
    $placeholder_pieces = [];

    foreach ($this->fields as $key => $value) {
      $data = $this->addPlaceholder($key, $value);
      $name_pieces[] = $key;
      $placeholder_pieces[] = $data['placeholder'];
    }

    return trim(strtr('INSERT INTO ___TABLE___ (___COLUMN_NAMES___) VALUES (___VALUES___)', [
      '___TABLE___' => $this->table,
      '___COLUMN_NAMES___' => implode(', ', $name_pieces),
      '___VALUES___' => implode(', ', $placeholder_pieces),
    ]));
  }

}