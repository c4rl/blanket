<?php

namespace spec\Blanket;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

use Blanket\Request;

class AppSpec extends ObjectBehavior {

  function it_is_initializable() {
    $this->shouldHaveType('Blanket\App');
  }

  function it_should_have_get_register() {
    $this->shouldThrow('ArgumentCountError')->duringGet();
    $this->shouldThrow('ArgumentCountError')->duringGet('path');
    $this->shouldThrow('TypeError')->duringGet('path', 'foo');
    $this->get('path', function () {})->shouldReturn(NULL);
  }

  function it_should_have_post_register() {
    $this->shouldThrow('ArgumentCountError')->duringPost();
    $this->shouldThrow('ArgumentCountError')->duringPost('path');
    $this->shouldThrow('TypeError')->duringPost('path', 'foo');
    $this->post('path', function () {})->shouldReturn(NULL);
  }

  function it_should_have_put_register() {
    $this->shouldThrow('ArgumentCountError')->duringPut();
    $this->shouldThrow('ArgumentCountError')->duringPut('path');
    $this->shouldThrow('TypeError')->duringPut('path', 'foo');
    $this->put('path', function () {})->shouldReturn(NULL);
  }

  function it_should_have_delete_register() {
    $this->shouldThrow('ArgumentCountError')->duringDelete();
    $this->shouldThrow('ArgumentCountError')->duringDelete('path');
    $this->shouldThrow('TypeError')->duringDelete('path', 'foo');
    $this->delete('path', function () {})->shouldReturn(NULL);
  }

  function it_should_not_register_unsupported_methods() {
    $this->shouldThrow('BadMethodCallException')->duringPatch();
  }

  function it_should_respond(Request $request) {
    $request->path = 'foo';
    $request->method = 'get';
    $this->get('foo', function (Request $request) {
      return 'Hi there';
    });
    $this->getResponse($request)->shouldReturn('Hi there');
  }

}
