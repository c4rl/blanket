<?php

use Blanket\Db\Db;

return [
  'models' => [
    // Add model class names here.
  ],
  'resources' => [
    // Add class names as a key => value, where the key is the base path for
    // the REST calls.

    // For example:
    // `blog => BlogPost::class,`

    // If a class is included here, it is not necessary to include it in the
    // models array.
  ],
  'storage' => new Db(sprintf('sqlite:%s/database/db.sqlite', __DIR__)),
];
