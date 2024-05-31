<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Stuff extends Model
{
    use SoftDeletes;
    protected $fillable = ["name", "category"];


    public function stock(){

    return $this->hasOne(StuffStock::class);

    }

    public function inboundStuffs(){

        return $this->hasMany(InboundStuff::class);

    }

    public function lendings(){

        return $this->hasMany(Lending::class);
        
    }
}

