<?php

namespace App\Models;

use App\Models\Concerns\HasContentStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Topic extends Model
{
    use HasContentStatus, HasFactory;

    protected $guarded = [];

    public function modules(): HasMany
    {
        return $this->hasMany(Module::class)->orderBy('sort');
    }

    public function questions(): HasMany
    {
        return $this->hasMany(Question::class);
    }
}
