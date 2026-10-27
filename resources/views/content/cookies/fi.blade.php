<x-layouts.app title="Evästeet">
    <article class="content">
        <h1>Evästeet</h1>
        <p>{{ config('app.name') }} asettaa vain palvelun toimintaan tarvittavia evästeitä. Emme käytä mainonta- tai markkinointievästeitä emmekä kolmannen osapuolen seurantaa.</p>
        <h2>Käyttämämme evästeet</h2>
        @cookieconsentinfo
        <h2>Paikallinen tallennustila</h2>
        <p>Vaalea tai tumma teemavalintasi tallennetaan selaimesi paikalliseen tallennustilaan nimellä <code>theme</code>. Se pysyy selaimessasi, eikä sitä lähetetä meille.</p>
        <h2>Valintasi</h2>
        <p>Voit muuttaa evästevalintaasi milloin tahansa:</p>
        <p>@cookieconsentbutton(action: 'reset', label: __('cookieConsent::cookies.manage'), attributes: ['class' => 'cookie-reset'])</p>
        <p>Voit myös poistaa evästeet selaimen asetuksista. Silloin palvelu unohtaa, mitkä äänet olivat sinun, ja voit mahdollisesti äänestää uudelleen.</p>
    </article>
</x-layouts.app>
