<?php

namespace Blanket\Db;

use Blanket\Storage\UpdateStatementInterface;

/**
 * Class DbUpdateStatement.
 *
 * @package Blanket
 */
class DbUpdateStatement extends AbstractDbStatement implements UpdateStatementInterface {

  /**
   * {@inheritdoc}
   */
  protected function getSqlStatement() {

    $field_updates = [];
    foreach ($this->fields as $key => $value) {
      $data = $this->addPlaceholder($key, $value);
      $field_updates[] = sprintf('%s = %s', $data['name'], $data['placeholder']);
    }

    return trim(strtr('UPDATE ___TABLE___ SET ___FIELDS___ ___CONDITIONS___', [
      '___TABLE___' => $this->table,
      '___FIELDS___' => implode(', ', $field_updates),
      '___CONDITIONS___' => $this->getConditionsString(),
    ]));
  }

}