<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Book extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'cover_image',
        'total_pages',
        'price_per_page',
    ];

    public function pages(): HasMany
    {
        return $this->hasMany(Page::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }
} 