<?php

namespace App\Models;

use Database\Factories\PollOptionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PollOption extends Model
{
    /** @use HasFactory<PollOptionFactory> */
    use HasFactory;

    /**
     * The attributes that are not mass assignable.
     *
     * @var list<string>
     */
    protected $guarded = [];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date' => 'date',
            'starts_at' => 'datetime',
        ];
    }

    /**
     * The text shown to voters. Times are shown in the given timezone.
     */
    public function displayLabel(string $timezone): string
    {
        if ($this->starts_at) {
            $local = $this->starts_at->copy()->setTimezone($timezone);

            return $local->isoFormat('LLLL').' ('.$local->format('T').')';
        }

        if ($this->date) {
            return $this->date->isoFormat('dddd, LL');
        }

        return (string) $this->label;
    }

    /**
     * @return BelongsTo<Poll, $this>
     */
    public function poll(): BelongsTo
    {
        return $this->belongsTo(Poll::class);
    }
}
