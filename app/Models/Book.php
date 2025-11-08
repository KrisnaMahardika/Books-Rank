<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Book extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'category_id',
        'author_id',
        'isbn',
        'publisher',
        'publication_year',
        'store_location',
        'status'
    ];

    public function category(): BelongsTo {
        return $this->belongsTo(Category::class);
    }

    public function author(): BelongsTo {
        return $this->belongsTo(Author::class);
    }

    public function ratings(): HasMany {
        return $this->hasMany(Rating::class);
    }

    public function getAvgRatingAttribute() {
        return number_format($this->ratings()->avg('rating'), 1);
    }

    public function getTotalRatingAttribute() {
        return $this->ratings()->count();
    }

    public function getTrendAttribute()
    {
        $overall = $this->ratings_avg_rating ?? null;
        $recent = $this->recent_avg_rating ?? null;

        if (is_null($overall) || is_null($recent)) {
            return 'neutral';
        }

        // logger()->info("Book {$this->id} - Overall: {$overall}, Recent: {$recent}");

        if ($recent > $overall) {
            return 'up';
        } elseif ($recent < $overall) {
            return 'down';
        }

        return 'neutral';
    }

    // cek status
    // public function isAvailable() {
    //     return $this->status === 'available';
    // }

    // public function isRented() {
    //     return $this->status === 'rented';
    // }

    // public function isReserved() {
    //     return $this->status === 'reserved';
    // }
}
