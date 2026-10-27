<?php

namespace App\Models;

use App\Enums\Category;
use App\Enums\PollPhase;
use App\Enums\VotingMethod;
use Carbon\CarbonInterface;
use Database\Factories\PollFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property Category $category
 * @property VotingMethod $voting_method
 * @property bool $includes_time
 * @property Carbon|null $suggestions_starts_at
 * @property Carbon|null $suggestions_ends_at
 * @property Carbon $voting_starts_at
 * @property Carbon $voting_ends_at
 * @property Carbon|null $closed_at
 * @property Carbon $expires_at
 */
class Poll extends Model
{
    /** @use HasFactory<PollFactory> */
    use HasFactory, Prunable;

    /**
     * The attributes that are not mass assignable.
     *
     * @var list<string>
     */
    protected $guarded = [];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = ['admin_token_hash'];

    /**
     * Keep the deletion date in line with the poll's end.
     */
    protected static function booted(): void
    {
        static::saving(function (Poll $poll): void {
            $poll->expires_at = ($poll->closed_at ?? $poll->voting_ends_at)
                ->copy()
                ->addDays(config('polls.retention_days'));
        });
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'category' => Category::class,
            'voting_method' => VotingMethod::class,
            'includes_time' => 'boolean',
            'suggestions_starts_at' => 'datetime',
            'suggestions_ends_at' => 'datetime',
            'voting_starts_at' => 'datetime',
            'voting_ends_at' => 'datetime',
            'closed_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    /**
     * Hash a secret token the way it is stored.
     */
    public static function hashToken(string $token): string
    {
        return hash('sha256', $token);
    }

    /**
     * Hash a voter token for this poll. The hash differs per poll, so the same
     * browser cannot be linked across polls from the database.
     */
    public function voterHash(string $token): string
    {
        return hash_hmac('sha256', $token, config('app.key').'|'.$this->id);
    }

    /**
     * How many options a voter ranks: the creator's limit, capped by the options.
     */
    public function rankingPlaces(): int
    {
        $count = $this->options()->count();

        return min($this->ranking_limit ?? $count, $count);
    }

    /**
     * Find the poll an admin token belongs to.
     */
    public static function findByAdminToken(string $token): ?self
    {
        return static::where('admin_token_hash', static::hashToken($token))->first();
    }

    /**
     * Work out the phase from the timestamps, so nothing has to flip a status.
     * Closing the suggestions early means setting suggestions_ends_at to now.
     */
    public function phase(?CarbonInterface $at = null): PollPhase
    {
        $at ??= now();

        if ($this->closed_at?->lte($at) || $this->voting_ends_at->lte($at)) {
            return PollPhase::Closed;
        }

        if ($this->voting_starts_at->lte($at)) {
            return PollPhase::Voting;
        }

        if ($this->suggestions_starts_at?->lte($at) && $this->suggestions_ends_at?->gt($at)) {
            return PollPhase::Suggestions;
        }

        return PollPhase::Scheduled;
    }

    /**
     * Get the polls that have passed their retention period.
     *
     * @return Builder<static>
     */
    public function prunable(): Builder
    {
        return static::query()->where('expires_at', '<', now());
    }

    /**
     * @return HasMany<PollOption, $this>
     */
    public function options(): HasMany
    {
        return $this->hasMany(PollOption::class)->orderBy('position');
    }

    /**
     * @return HasMany<Vote, $this>
     */
    public function votes(): HasMany
    {
        return $this->hasMany(Vote::class);
    }
}
