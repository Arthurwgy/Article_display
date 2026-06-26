<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SensitiveWord extends Model
{
    protected $fillable = [
        'word',
        'level',
        'group_name',
    ];
}
