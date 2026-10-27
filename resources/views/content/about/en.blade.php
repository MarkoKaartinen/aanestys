<x-layouts.app title="About">
    <article class="content">
        <h1>About</h1>
        <p>{{ config('app.name') }} is a free poll service. Create a poll, share the link and let people vote. There are no accounts, no ads and no tracking.</p>
        <h2>Why it exists</h2>
        <p>Deciding things together should be simple: which book to read next, which evening works for everyone, which game to stream. You should not need to sign up or hand over personal data to ask a group a question.</p>
        <h2>What you can do</h2>
        <ul>
            <li>Choose one option, several options, or rank the options in order of preference.</li>
            <li>Let participants suggest the options before voting starts.</li>
            <li>Plan dates and times, with timezones handled for everyone.</li>
            <li>Follow the results live and share the final results.</li>
        </ul>
        <h2>Privacy first</h2>
        <p>Polls are not listed anywhere and search engines are asked not to index them. Only people with the link can find a poll. Polls and their votes are deleted {{ config('polls.retention_days') }} days after voting ends. Read the <a href="{{ route('privacy') }}">privacy policy</a> for details.</p>
    </article>
</x-layouts.app>
