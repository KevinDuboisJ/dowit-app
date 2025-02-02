<?php

namespace App\Traits;

trait HasExternalConnection
{
  protected $table = 'externalusers.company';
  protected $connection = 'externalusers';

  public function __construct(array $attributes = [])
  {
    parent::__construct($attributes);
    // Check the APP_ENV value and adjust the table name accordingly
    // Adding externaluserstest. to the $table makes the whereHas work for company relationship that resides in other db.
    // But this seems only to work in mysql.
    if (config('app.env') === 'local') {
      $this->table = 'externaluserstest.company';
    }
  }


}
