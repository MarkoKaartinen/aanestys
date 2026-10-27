<x-layouts.app title="Tietoa palvelusta">
    <article class="content">
        <h1>Tietoa palvelusta</h1>
        <p>{{ config('app.name') }} on ilmainen äänestyspalvelu. Luo äänestys, jaa linkki ja anna ihmisten äänestää. Ei tilejä, ei mainoksia eikä seurantaa.</p>
        <h2>Miksi tämä on olemassa</h2>
        <p>Asioista päättämisen pitäisi olla helppoa: mikä kirja luetaan seuraavaksi, mikä ilta sopii kaikille, mitä peliä striimataan. Ryhmältä kysymiseen ei pitäisi tarvita rekisteröitymistä eikä henkilötietojen luovuttamista.</p>
        <h2>Mitä voit tehdä</h2>
        <ul>
            <li>Valita yhden vaihtoehdon, useita vaihtoehtoja tai järjestää vaihtoehdot paremmuusjärjestykseen.</li>
            <li>Antaa osallistujien ehdottaa vaihtoehdot ennen äänestyksen alkua.</li>
            <li>Suunnitella päiviä ja kellonaikoja niin, että aikavyöhykkeet huomioidaan kaikille.</li>
            <li>Seurata tuloksia reaaliajassa ja jakaa lopulliset tulokset.</li>
        </ul>
        <h2>Yksityisyys ensin</h2>
        <p>Äänestyksiä ei listata missään, ja hakukoneita pyydetään olemaan indeksoimatta niitä. Äänestyksen löytää vain se, jolla on linkki. Äänestykset ja niiden äänet poistetaan {{ config('polls.retention_days') }} päivää äänestyksen päättymisen jälkeen. Lue lisää <a href="{{ route('privacy') }}">tietosuojaselosteesta</a>.</p>
    </article>
</x-layouts.app>
