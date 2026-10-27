<x-layouts.app title="Cookies">
    <article class="content">
        <h1>Cookies</h1>
        <p>{{ config('app.name') }} only sets cookies that are needed for it to work. We use no advertising or marketing cookies and no third party tracking.</p>
        <h2>Cookies we use</h2>
        @cookieconsentinfo
        <h2>Local storage</h2>
        <p>Your light or dark theme choice is saved in your browser's local storage under <code>theme</code>. It stays in your browser and is never sent to us.</p>
        <h2>Your choices</h2>
        <p>You can change your cookie choice at any time:</p>
        <p>@cookieconsentbutton(action: 'reset', label: __('cookieConsent::cookies.manage'), attributes: ['class' => 'cookie-reset'])</p>
        <p>You can also delete cookies in your browser settings. The service then forgets which poll votes were yours, so you may be able to vote again.</p>
    </article>
</x-layouts.app>
