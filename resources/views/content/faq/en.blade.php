<x-layouts.app title="Frequently asked questions">
    <article class="content">
        <h1>Frequently asked questions</h1>
        <h2>Do I need an account?</h2>
        <p>No. You create a poll without signing up. You get an admin link that lets you manage the poll, and a share link for the voters.</p>
        <h2>I lost my admin link. Can you recover it?</h2>
        <p>No. We only store a hash of the admin link, so we cannot recover or reset it. Save the link when you create the poll.</p>
        <h2>How do the voting methods work?</h2>
        <ul>
            <li><strong>Single choice:</strong> each voter picks one option. The option with the most votes wins.</li>
            <li><strong>Multiple choice:</strong> each voter can pick several options, up to a limit the creator may set. The option with the most votes wins.</li>
            <li><strong>Ranking:</strong> each voter puts options in order of preference. With a full ranking of 10 options the first place gets 10 points, the second 9, and so on down to 1. With a top 5 ranking, the voter picks and orders five options that get 5, 4, 3, 2 and 1 points, and the rest get none. The points are added up and the option with the most points wins (the Borda count). Equal scores are shown as a tie.</li>
        </ul>
        <h2>Can someone vote twice?</h2>
        <p>Your browser gets a cookie that identifies your vote, so one browser has one vote per poll and can change it while voting is open. This is a reasonable protection, not a guarantee: someone who clears their cookies or uses another browser can vote again.</p>
        <h2>Can the creator see how I voted?</h2>
        <p>No. Votes are stored without names, and the creator and everyone else only see totals.</p>
        <h2>How do timezones work?</h2>
        <p>When a poll has dates with times, the creator picks a timezone. Voters see the times in their own timezone and can switch to another one.</p>
        <h2>What happens to my poll afterwards?</h2>
        <p>The poll and its votes are deleted {{ config('polls.retention_days') }} days after voting ends. The creator can delete a poll earlier from the admin page.</p>
        <h2>Is it free? Where is the catch?</h2>
        <p>It is free, without ads and without selling data. There is no catch.</p>
    </article>
</x-layouts.app>
