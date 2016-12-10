<?php

namespace Blanket\Exception;

/**
 * Class Http500Exception.
 *
 * For 500 Internal Server Error HTTP code.
 *
 * @package Blanket
 */
class Http500Exception extends \RuntimeException {

  protected $message = 'Internal Server Error';

  protected $code = 500;

}
