<?php

use Blanket\App;
use Blanket\Request;
use PHPUnit\Framework\TestCase;

/**
 * Class AppGetTest.
 */
class AppGetTest extends TestCase {

  function testGetRequest() {
    /** @var Request $request */
    $request = $this->getMockBuilder(Request::class)->getMock();

    $data = ['hi' => 'there'];

    $app = new App();
    $app->get('funky', function() use ($data) {
      return $data;
    });

    $request->method = 'get';
    $request->path = 'funky';

    $this->assertEquals($data, $app->getResponse($request));
  }

}
