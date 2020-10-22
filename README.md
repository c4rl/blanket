# Blanket

Blanket is a Simple REST microframework similar to Rack or Silex.

Dependencies: composer, phpunit, phpspec

To run tests:

    $ ./vendor/phpspec/phpspec/bin/phpspec run
    $ ./vendor/bin/phpunit --configuration=./phpunit.xml

Sample project structure:

    composer.json
    composer.lock
    config.php
    database/
      db.sqlite
      schema.sql
    example.config.php
    php.ini
    phpunit.php
    phpunit.xml
    public/
      index.php
    src/
      ...
    vendor/
      ...

To run app:

    $ php -S localhost:8080 -t ./public -c php.ini
    
Example public/index.php:

    <?php
    
    require __DIR__ . '/../vendor/autoload.php';
    
    use Blanket\App;
    use Blanket\Request;
    
    use Etc\Model\BlogPost;
    
    // Copy config.example.php to config.php
    $app = new App(require '../config.php');
    
    // Simple string returns text/html.
    $app->get('foo', function () {
      return 'bar';
    });
    
    // Returning structured data returns application/json
    $app->get('post/:id', function ($id) {
      return BlogPost::findOrFail($id)->getAttributes();
    });
    // However, for basic needs it is better in this example to add
    // BlogPost in `'resources'` in the config file, which will automatically
    // set-up basic CRUD via POST/GET/PUT/DELETE calls.
    
    $app->run(Request::createFromGlobals());
