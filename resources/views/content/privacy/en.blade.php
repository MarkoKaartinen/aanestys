<x-layouts.app title="Privacy policy">
    <article class="content">
        <h1>Privacy policy</h1>
        <p>This policy explains what data {{ config('app.name') }} handles. We keep it to the minimum needed to run polls.</p>

        <h2>Who is responsible</h2>
        <p>
            @if (config('polls.operator.name'))
                {{ config('polls.operator.name') }}
            @else
                The operator of this service
            @endif
            is the data controller.
            @if (config('polls.operator.email'))
                Contact: <a href="mailto:{{ config('polls.operator.email') }}">{{ config('polls.operator.email') }}</a>.
            @endif
        </p>

        <h2>What we store</h2>
        <ul>
            <li><strong>Poll content:</strong> the title, description, options, dates and the creator's timezone.</li>
            <li><strong>Votes:</strong> which options a voter chose, stored without a name. A voter is recognised only by a random token kept in a cookie. We store a keyed hash of that token, different for every poll, so votes cannot be linked to a person or across polls.</li>
            <li><strong>Admin link:</strong> we store only a hash of the secret in the admin link.</li>
            <li><strong>IP address:</strong> used to limit abuse. We keep a keyed hash of the address in the cache for a short time (minutes to an hour). The address itself is not stored with polls or votes. The web server may write IP addresses to its access logs for security and troubleshooting, and they are kept for a short period.</li>
        </ul>
        <p>We do not ask for names or email addresses, and we do not use advertising or marketing trackers.</p>

        <h2>Why we process it</h2>
        <p>To provide the service you asked for (running polls and counting votes) and to keep it secure. The legal basis is our legitimate interest in providing and protecting the service.</p>

        <h2>Cookies and similar storage</h2>
        <p>We only use cookies that the service needs to work: a session cookie, a security (CSRF) cookie, a language cookie, a voter cookie and a cookie that remembers your cookie choice. Your theme choice (light or dark) is kept in your browser's local storage and is never sent to us. See the <a href="{{ route('cookies') }}">cookies page</a> for the full list.</p>

        <h2>Analytics</h2>
        <p>We plan to measure visits with a self-hosted Plausible instance. It uses no cookies, does not track individuals and stores no personal data.</p>

        <h2>Where data is stored and who processes it</h2>
        <p>The service runs on servers in Finland (Hetzner). Data stays within the EU. We do not sell data or share it with advertisers.</p>

        <h2>How long we keep data</h2>
        <p>A poll and all its votes are deleted {{ config('polls.retention_days') }} days after voting ends. The creator can delete a poll earlier from the admin page. Cache entries expire within an hour.</p>

        <h2>Your rights</h2>
        <p>You have the right to access, correct and delete your data and to object to its processing. Because votes are anonymous we usually cannot tell which votes are yours. The creator of a poll can delete the poll at any time. You can complain to the Data Protection Ombudsman (tietosuoja.fi).</p>
    </article>
</x-layouts.app>
