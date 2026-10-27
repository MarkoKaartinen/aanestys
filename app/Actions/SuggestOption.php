<?php

namespace App\Actions;

use App\Enums\Category;
use App\Enums\PollPhase;
use App\Models\Poll;
use App\Models\PollOption;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class SuggestOption
{
    public const MAX_OPTIONS = 50;

    /**
     * Add an option suggested by a participant while the suggestion round is open.
     *
     * @param  array{label?: string|null, date?: string|null, time?: string|null}  $input
     * @param  string|null  $timezone  The zone the participant entered a time in; the poll's zone by default.
     *
     * @throws ValidationException
     */
    public function handle(Poll $poll, string $voterToken, array $input, ?string $timezone = null): PollOption
    {
        if ($poll->phase() !== PollPhase::Suggestions) {
            $this->fail(__('Suggestions are not open.'));
        }

        $hash = $poll->voterHash($voterToken);

        if ($poll->options()->where('suggested_by_hash', $hash)->count() >= $poll->max_suggestions) {
            $this->fail(trans_choice('You can suggest at most :count option.|You can suggest at most :count options.', $poll->max_suggestions));
        }

        if ($poll->options()->count() >= self::MAX_OPTIONS) {
            $this->fail(__('This poll has reached the maximum number of options.'));
        }

        $input = $this->validated($poll, $input);
        $attributes = $poll->optionAttributes($input, $timezone);

        if ($this->isDuplicate($poll, $attributes)) {
            $this->fail(__('This option has already been suggested.'));
        }

        return $poll->options()->create([
            ...$attributes,
            'position' => ((int) $poll->options()->max('position')) + 1,
            'suggested_by_hash' => $hash,
        ]);
    }

    /**
     * @param  array{label?: string|null, date?: string|null, time?: string|null}  $input
     * @return array{label?: string|null, date?: string|null, time?: string|null}
     *
     * @throws ValidationException
     */
    private function validated(Poll $poll, array $input): array
    {
        $isDates = $poll->category === Category::Dates;

        $rules = $isDates
            ? ['date' => ['required', 'date_format:Y-m-d'], 'time' => $poll->includes_time ? ['required', 'date_format:H:i'] : ['nullable']]
            : ['label' => ['required', 'string', 'max:200']];

        $validator = Validator::make(
            $isDates ? $input : ['label' => trim((string) ($input['label'] ?? ''))],
            $rules,
            attributes: ['label' => __('option'), 'date' => __('date'), 'time' => __('time')],
        );

        if ($validator->fails()) {
            $this->fail($validator->errors()->first());
        }

        return $isDates ? $input : ['label' => trim((string) ($input['label'] ?? ''))];
    }

    /**
     * @param  array{label: string|null, date: string|null, starts_at: CarbonInterface|null}  $attributes
     */
    private function isDuplicate(Poll $poll, array $attributes): bool
    {
        return $poll->options()->get()->contains(function (PollOption $existing) use ($attributes): bool {
            if ($attributes['starts_at']) {
                return $existing->starts_at?->equalTo($attributes['starts_at']) ?? false;
            }

            if ($attributes['date']) {
                return $existing->date?->toDateString() === $attributes['date'];
            }

            return $this->normalize((string) $existing->label) === $this->normalize((string) $attributes['label']);
        });
    }

    private function normalize(string $label): string
    {
        return mb_strtolower((string) preg_replace('/\s+/u', ' ', trim($label)));
    }

    /**
     * @throws ValidationException
     */
    private function fail(string $message): never
    {
        throw ValidationException::withMessages(['suggestion' => $message]);
    }
}
