<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = ['user_id', 'status', 'items'];

    protected $casts = [
        'items' => 'array',  // Automatically cast items as an array
    ];
}
