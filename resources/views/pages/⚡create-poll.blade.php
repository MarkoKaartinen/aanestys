<?php

use App\Actions\CreatePoll;
use App\Enums\Category;
use App\Enums\VotingMethod;
use App\Support\Throttle;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layouts.app', ['wide' => true])] class extends Component
{
    public const MAX_OPTIONS = 50;

    public string $title = '';

    public string $description = '';

    public string $category = 'custom';

    public string $votingMethod = 'single';

    public ?int $maxChoices = null;

    public ?int $rankingLimit = null;

    public bool $includesTime = false;

    public string $timezone = 'UTC';

    public string $endsAt = '';

    public bool $liveResultsVisible = true;

    public bool $collectSuggestions = false;

    public string $suggestionsEndAt = '';

    public int $maxSuggestions = 1;

    /** @var list<array{id: string, label: string, date: string, time: string}> */
    public array $options = [];

    public function mount(): void
    {
        $this->options = [$this->blankOption(), $this->blankOption()];
    }

    /**
     * @return array{id: string, label: string, date: string, time: string}
     */
    private function blankOption(): array
    {
        return ['id' => Str::random(8), 'label' => '', 'date' => '', 'time' => ''];
    }

    public function addOption(): void
    {
        if (count($this->options) < self::MAX_OPTIONS) {
            $this->options[] = $this->blankOption();
        }
    }

    public function removeOption(int $index): void
    {
        if (count($this->options) > ($this->collectSuggestions ? 0 : 2)) {
            unset($this->options[$index]);
            $this->options = array_values($this->options);
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        $isDates = $this->category === Category::Dates->value;

        return [
            'title' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:2000'],
            'category' => ['required', Rule::enum(Category::class)],
            'votingMethod' => ['required', Rule::enum(VotingMethod::class)],
            'maxChoices' => ['nullable', 'integer', 'min:1', ...($this->collectSuggestions ? [] : ['max:'.count($this->options)])],
            'rankingLimit' => ['nullable', 'integer', 'min:1', ...($this->collectSuggestions ? [] : ['max:'.count($this->options)])],
            'collectSuggestions' => ['boolean'],
            'liveResultsVisible' => ['boolean'],
            'suggestionsEndAt' => [$this->collectSuggestions ? 'required' : 'nullable', 'date'],
            'maxSuggestions' => ['integer', 'min:1', 'max:20'],
            'includesTime' => ['boolean'],
            'timezone' => ['required', 'timezone:all'],
            'endsAt' => ['required', 'date'],
            'options' => ['array', 'min:'.($this->collectSuggestions ? 0 : 2), 'max:'.self::MAX_OPTIONS],
            'options.*.label' => $isDates ? ['nullable'] : ['required', 'string', 'max:200', 'distinct:ignore_case'],
            'options.*.date' => $isDates
                ? ['required', 'date', ...($this->includesTime ? [] : ['distinct'])]
                : ['nullable'],
            'options.*.time' => $isDates && $this->includesTime ? ['required', 'date_format:H:i'] : ['nullable'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function validationAttributes(): array
    {
        return [
            'title' => __('title'),
            'description' => __('description'),
            'maxChoices' => __('maximum choices'),
            'rankingLimit' => __('number of places'),
            'timezone' => __('timezone'),
            'endsAt' => __('end time'),
            'suggestionsEndAt' => __('suggestions end time'),
            'maxSuggestions' => __('suggestions per person'),
            'options' => __('options'),
            'options.*.label' => __('option'),
            'options.*.date' => __('date'),
            'options.*.time' => __('time'),
        ];
    }

    public function save(CreatePoll $createPoll): void
    {
        if ($this->collectSuggestions) {
            $this->options = array_values(array_filter(
                $this->options,
                fn (array $option): bool => trim($option['label']) !== '' || $option['date'] !== '',
            ));
        }

        $this->validate();

        if (Throttle::exceeded('create-poll', 10, 3600)) {
            $this->addError('title', __('Too many polls created. Try again later.'));

            return;
        }

        $endsAt = Carbon::parse($this->endsAt, $this->timezone)->utc();
        $suggestionsEnd = $this->collectSuggestions ? Carbon::parse($this->suggestionsEndAt, $this->timezone)->utc() : null;

        if ($endsAt->lte(now())) {
            $this->addError('endsAt', __('The end time must be in the future.'));

            return;
        }

        if ($suggestionsEnd && ($suggestionsEnd->lte(now()) || $suggestionsEnd->gte($endsAt))) {
            $this->addError('suggestionsEndAt', __('Suggestions must end in the future and before voting ends.'));

            return;
        }

        ['admin_token' => $token] = $createPoll->handle([
            'title' => trim($this->title),
            'description' => trim($this->description) ?: null,
            'category' => Category::from($this->category),
            'voting_method' => VotingMethod::from($this->votingMethod),
            'max_choices' => $this->maxChoices,
            'ranking_limit' => $this->rankingLimit,
            'timezone' => $this->timezone,
            'includes_time' => $this->includesTime,
            'voting_ends_at' => $endsAt,
            'suggestions_ends_at' => $suggestionsEnd,
            'max_suggestions' => $this->maxSuggestions,
            'live_results_visible' => $this->liveResultsVisible,
            'options' => array_map(fn (array $option): array => [
                'label' => trim($option['label']) ?: null,
                'date' => $option['date'] ?: null,
                'time' => $option['time'] ?: null,
            ], $this->options),
        ]);

        $this->redirectRoute('poll.manage', ['token' => $token], navigate: true);
    }
};
?>

<div
    x-data
    x-init="
        const zone = Intl.DateTimeFormat().resolvedOptions().timeZone
        if (zone) $wire.timezone = zone
        if (! $wire.endsAt) {
            $wire.endsAt = new Date(Date.now() + 7 * 864e5 - new Date().getTimezoneOffset() * 6e4).toISOString().slice(0, 16)
        }
    "
    class="space-y-24"
>
    {{-- Hero --}}
    <section class="relative isolate grid items-center gap-12 pt-4 lg:grid-cols-[1.1fr_0.9fr] lg:pt-10">
        <div class="pointer-events-none absolute top-[-6rem] left-1/2 -z-10 h-[34rem] w-screen -translate-x-1/2 bg-glow"></div>
        <div class="pointer-events-none absolute top-[-6rem] left-1/2 -z-10 h-[34rem] w-screen -translate-x-1/2 bg-dots opacity-60"></div>

        <div class="space-y-6">
            <p class="eyebrow inline-flex items-center gap-2 rounded-full border border-line-strong bg-surface px-3 py-1">
                <span class="size-1.5 rounded-full bg-accent"></span>
                {{ __('Free') }} · {{ __('No accounts') }} · {{ __('No ads') }}
            </p>

            <h1 class="text-4xl leading-[1.05] font-semibold sm:text-5xl lg:text-6xl">{{ __('Ask a group. Get an answer.') }}</h1>

            <p class="max-w-xl text-lg text-muted">
                {{ __('Create a poll in seconds, share the link and watch the results come in. Pick one, pick several or rank the options. Nothing to sign up for.') }}
            </p>

            <div class="flex flex-wrap gap-3 pt-2">
                <a href="#create" class="btn-primary px-5 py-2.5 text-base">{{ __('Start a poll') }}</a>
                <a href="#how" class="btn-secondary px-5 py-2.5 text-base">{{ __('How it works') }}</a>
            </div>
        </div>

        {{-- An example of what the results look like. Decorative. --}}
        <div class="card overflow-hidden shadow-xl" aria-hidden="true">
            <div class="flex items-center justify-between border-b border-line bg-surface-2 px-4 py-2.5">
                <div class="flex gap-1.5">
                    <span class="size-2.5 rounded-full bg-line-strong"></span>
                    <span class="size-2.5 rounded-full bg-line-strong"></span>
                    <span class="size-2.5 rounded-full bg-line-strong"></span>
                </div>
                <span class="font-mono text-xs text-muted">/p/k3x9a2mq7p</span>
                <span class="eyebrow inline-flex items-center gap-1.5 text-accent-strong"><span class="size-1.5 animate-pulse rounded-full bg-accent"></span>{{ __('Live') }}</span>
            </div>

            <div class="space-y-4 p-5">
                <p class="font-medium">{{ __('Which book should we read next?') }}</p>

                <div class="space-y-3">
                    @foreach ([['Dune', 48, true], ['Piranesi', 32, false], ['Tuntematon sotilas', 20, false]] as [$name, $share, $leads])
                        <div>
                            <div class="mb-1.5 flex justify-between text-sm">
                                <span class="font-medium">{{ $name }}</span>
                                <span class="font-mono text-muted tabular-nums">{{ $share }}%</span>
                            </div>
                            <div class="h-2 rounded-full bg-surface-2">
                                <div class="{{ $leads ? 'bg-winner' : 'bg-accent' }} h-2 rounded-full" style="width: {{ $share * 2 }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <p class="font-mono text-xs text-muted">{{ trans_choice(':count vote|:count votes', 47) }}</p>
            </div>
        </div>
    </section>

    {{-- Features --}}
    <section class="space-y-8" aria-labelledby="features">
        <div class="max-w-2xl space-y-2">
            <p class="eyebrow">{{ __('Why') }} {{ config('app.name') }}</p>
            <h2 id="features" class="text-3xl font-semibold">{{ __('Simple for you, fair for everyone.') }}</h2>
        </div>

        <div class="grid gap-px overflow-hidden rounded-xl border border-line bg-line sm:grid-cols-2 lg:grid-cols-3">
            @foreach ([
                [__('No accounts'), __('Creating and voting needs nothing but a link. No sign-up, no email address.')],
                [__('Private by default'), __('Polls are unlisted and kept out of search engines. They are deleted :days days after voting ends.', ['days' => config('polls.retention_days')])],
                [__('Fair counting'), __('Single choice, multiple choice, or ranking with Borda points. Ties are shown as ties.')],
                [__('Live results'), __('Watch votes arrive in real time, or hide them until voting ends. Streaming? There is a view made for OBS.')],
                [__('Dates and timezones'), __('Offer dates and times. Everyone sees them in their own timezone.')],
                [__('Suggestions first'), __('Let participants suggest the options before voting starts, then vote on them.')],
            ] as $index => [$title, $text])
                <div class="space-y-2 bg-surface p-6">
                    <p class="font-mono text-xs text-accent">{{ str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) }}</p>
                    <h3 class="font-semibold">{{ $title }}</h3>
                    <p class="text-sm text-muted">{{ $text }}</p>
                </div>
            @endforeach
        </div>
    </section>

    {{-- How it works --}}
    <section id="how" class="space-y-8" aria-labelledby="how-title">
        <div class="max-w-2xl space-y-2">
            <p class="eyebrow">{{ __('Three steps') }}</p>
            <h2 id="how-title" class="text-3xl font-semibold">{{ __('How it works') }}</h2>
        </div>

        <ol class="grid gap-8 sm:grid-cols-3">
            @foreach ([
                [__('Create'), __('Write the question and the options.')],
                [__('Share'), __('Send the link to the people who should vote.')],
                [__('Watch'), __('Follow the results live and share the final result.')],
            ] as $index => [$title, $text])
                <li class="space-y-3 border-t-2 border-line-strong pt-4">
                    <p class="font-mono text-sm text-accent">{{ $index + 1 }} /</p>
                    <h3 class="text-xl font-semibold">{{ $title }}</h3>
                    <p class="text-muted">{{ $text }}</p>
                </li>
            @endforeach
        </ol>
    </section>

    {{-- The form --}}
    <section id="create" class="mx-auto max-w-3xl space-y-8" aria-labelledby="create-title">
        <div class="space-y-2">
            <p class="eyebrow">{{ __('Start') }}</p>
            <h2 id="create-title" class="text-3xl font-semibold">{{ __('Create your poll') }}</h2>
        </div>

        <form wire:submit="save" class="card space-y-10 p-6 sm:p-8" novalidate>
            <fieldset class="space-y-5">
                <legend class="eyebrow mb-4 text-accent"><span class="mr-2">01</span>{{ __('Question') }}</legend>

                <div>
                    <label for="title" class="field-label">{{ __('Question') }}</label>
                    <input id="title" type="text" wire:model="title" class="field-input" maxlength="200" placeholder="{{ __('Which book should we read next?') }}">
                    @error('title') <p class="field-error">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="description" class="field-label">{{ __('Description (optional)') }}</label>
                    <textarea id="description" wire:model="description" rows="3" class="field-input" maxlength="2000"></textarea>
                    @error('description') <p class="field-error">{{ $message }}</p> @enderror
                </div>
            </fieldset>

            <fieldset class="space-y-5">
                <legend class="eyebrow mb-4 text-accent"><span class="mr-2">02</span>{{ __('Options') }}</legend>

                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <label for="category" class="field-label">{{ __('Options are') }}</label>
                        <select id="category" wire:model.live="category" class="field-input">
                            <option value="custom">{{ __('Free text') }}</option>
                            <option value="dates">{{ __('Dates') }}</option>
                        </select>
                    </div>

                    <div>
                        <label for="votingMethod" class="field-label">{{ __('Voting method') }}</label>
                        <select id="votingMethod" wire:model.live="votingMethod" class="field-input">
                            <option value="single">{{ __('Single choice') }}</option>
                            <option value="multiple">{{ __('Multiple choice') }}</option>
                            <option value="ranking">{{ __('Ranking') }}</option>
                        </select>
                    </div>
                </div>

                @if ($votingMethod === 'multiple')
                    <div>
                        <label for="maxChoices" class="field-label">{{ __('Maximum choices per voter (optional)') }}</label>
                        <input id="maxChoices" type="number" min="1" wire:model="maxChoices" class="field-input sm:w-40">
                        @error('maxChoices') <p class="field-error">{{ $message }}</p> @enderror
                    </div>
                @endif

                @if ($votingMethod === 'ranking')
                    <div>
                        <label for="rankingLimit" class="field-label">{{ __('Places to rank (optional)') }}</label>
                        <input id="rankingLimit" type="number" min="1" wire:model="rankingLimit" class="field-input sm:w-40">
                        <p class="field-hint">{{ __('Leave empty to rank every option. With 5, each voter picks and orders their top 5.') }}</p>
                        @error('rankingLimit') <p class="field-error">{{ $message }}</p> @enderror
                    </div>
                @endif

                @if ($category === 'dates')
                    <label class="flex items-center gap-2">
                        <input type="checkbox" wire:model.live="includesTime" class="size-4 accent-[var(--accent)]">
                        <span>{{ __('Include a time of day') }}</span>
                    </label>
                @endif

                <label class="flex items-center gap-2">
                    <input type="checkbox" wire:model.live="collectSuggestions" class="size-4 accent-[var(--accent)]">
                    <span>{{ __('Let participants suggest options first') }}</span>
                </label>

                @if ($collectSuggestions)
                    <div class="grid gap-5 rounded-lg border border-line bg-surface-2 p-4 sm:grid-cols-2">
                        <div>
                            <label for="suggestionsEndAt" class="field-label">{{ __('Suggestions end (voting starts)') }}</label>
                            <input id="suggestionsEndAt" type="datetime-local" wire:model="suggestionsEndAt" class="field-input">
                            @error('suggestionsEndAt') <p class="field-error">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="maxSuggestions" class="field-label">{{ __('Suggestions per person') }}</label>
                            <input id="maxSuggestions" type="number" min="1" max="20" wire:model="maxSuggestions" class="field-input sm:w-32">
                            @error('maxSuggestions') <p class="field-error">{{ $message }}</p> @enderror
                        </div>
                    </div>
                @endif

                <div class="space-y-3">
                    <p class="field-label">{{ $collectSuggestions ? __('Starting options (optional)') : __('Options') }}</p>

                    @foreach ($options as $index => $option)
                        <div wire:key="option-{{ $option['id'] }}" class="flex items-start gap-2">
                            <span class="mt-2.5 w-6 shrink-0 text-right font-mono text-xs text-muted">{{ $index + 1 }}</span>

                            <div class="grid flex-1 gap-2 sm:grid-cols-2">
                                @if ($category === 'dates')
                                    <div>
                                        <input type="date" wire:model="options.{{ $index }}.date" class="field-input" aria-label="{{ __('Date') }} {{ $index + 1 }}">
                                        @error("options.$index.date") <p class="field-error">{{ $message }}</p> @enderror
                                    </div>
                                    @if ($includesTime)
                                        <div>
                                            <input type="time" wire:model="options.{{ $index }}.time" class="field-input" aria-label="{{ __('Time') }} {{ $index + 1 }}">
                                            @error("options.$index.time") <p class="field-error">{{ $message }}</p> @enderror
                                        </div>
                                    @endif
                                @else
                                    <div class="sm:col-span-2">
                                        <input type="text" wire:model="options.{{ $index }}.label" class="field-input" maxlength="200" aria-label="{{ __('Option') }} {{ $index + 1 }}">
                                        @error("options.$index.label") <p class="field-error">{{ $message }}</p> @enderror
                                    </div>
                                @endif
                            </div>

                            @if (count($options) > ($collectSuggestions ? 0 : 2))
                                <button type="button" wire:click="removeOption({{ $index }})" class="btn-secondary px-3" aria-label="{{ __('Remove option') }} {{ $index + 1 }}">&times;</button>
                            @endif
                        </div>
                    @endforeach

                    @error('options') <p class="field-error">{{ $message }}</p> @enderror

                    @if (count($options) < $this::MAX_OPTIONS)
                        <button type="button" wire:click="addOption" class="btn-secondary">+ {{ __('Add option') }}</button>
                    @endif
                </div>
            </fieldset>

            <fieldset class="space-y-5">
                <legend class="eyebrow mb-4 text-accent"><span class="mr-2">03</span>{{ __('Timing') }}</legend>

                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <label for="endsAt" class="field-label">{{ __('Voting ends') }}</label>
                        <input id="endsAt" type="datetime-local" wire:model="endsAt" class="field-input">
                        @error('endsAt') <p class="field-error">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="timezone" class="field-label">{{ __('Timezone') }}</label>
                        <select id="timezone" wire:model="timezone" class="field-input">
                            @foreach (DateTimeZone::listIdentifiers() as $identifier)
                                <option value="{{ $identifier }}">{{ $identifier }}</option>
                            @endforeach
                        </select>
                        @error('timezone') <p class="field-error">{{ $message }}</p> @enderror
                    </div>
                </div>
            </fieldset>

            <fieldset class="space-y-5">
                <legend class="eyebrow mb-4 text-accent"><span class="mr-2">04</span>{{ __('Results') }}</legend>

                <label class="flex items-center gap-2">
                    <input type="checkbox" wire:model="liveResultsVisible" class="size-4 accent-[var(--accent)]">
                    <span>{{ __('Show the results to participants while voting is open') }}</span>
                </label>
            </fieldset>

            <div class="space-y-4 border-t border-line pt-6">
                <button type="submit" class="btn-primary px-6 py-2.5 text-base" wire:loading.attr="disabled">{{ __('Create poll') }}</button>

                <p class="field-hint">
                    {{ __('Polls and their votes are deleted :days days after voting ends.', ['days' => config('polls.retention_days')]) }}
                </p>
            </div>
        </form>
    </section>
</div>
