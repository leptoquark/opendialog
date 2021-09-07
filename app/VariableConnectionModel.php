<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

abstract class VariableConnectionModel extends Model
{
    public function __construct(array $attributes = [])
    {
        $this->connection = config('database.variable_model_connection', config('database.default'));

        parent::__construct($attributes);
    }
}
