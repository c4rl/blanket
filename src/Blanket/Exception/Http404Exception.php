<?php

namespace Blanket\Exception;

/**
 * Class Http404Exception.
 *
 * For 404 Not Found HTTP code.
 *
 * @package Blanket
 */
class Http404Exception extends \RuntimeException {

  protected $message = 'Not Found';

  protected $code = 404;

}
