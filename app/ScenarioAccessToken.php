<?php


namespace App;


use Illuminate\Database\Eloquent\Model;

class ScenarioAccessToken extends Model
{
    protected $fillable = ['scenario_id', 'access_token_plaintext'];
}
