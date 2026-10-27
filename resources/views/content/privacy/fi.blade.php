<x-layouts.app title="Tietosuojaseloste">
    <article class="content">
        <h1>Tietosuojaseloste</h1>
        <p>Tässä selosteessa kerrotaan, mitä tietoja {{ config('app.name') }} käsittelee. Pidämme ne mahdollisimman vähäisinä.</p>

        <h2>Rekisterinpitäjä</h2>
        <p>
            @if (config('polls.operator.name'))
                {{ config('polls.operator.name') }}
            @else
                Palvelun ylläpitäjä
            @endif
            on rekisterinpitäjä.
            @if (config('polls.operator.email'))
                Yhteystiedot: <a href="mailto:{{ config('polls.operator.email') }}">{{ config('polls.operator.email') }}</a>.
            @endif
        </p>

        <h2>Mitä tallennamme</h2>
        <ul>
            <li><strong>Äänestyksen sisältö:</strong> otsikko, kuvaus, vaihtoehdot, päivämäärät ja luojan aikavyöhyke.</li>
            <li><strong>Äänet:</strong> mitä vaihtoehtoja äänestäjä valitsi, tallennettuna ilman nimeä. Äänestäjä tunnistetaan vain evästeessä olevasta satunnaisesta tunnisteesta. Tallennamme tunnisteesta avaimellisen tiivisteen, joka on eri jokaisessa äänestyksessä, joten ääniä ei voi yhdistää henkilöön tai eri äänestysten välillä.</li>
            <li><strong>Hallintalinkki:</strong> tallennamme hallintalinkin salaisuudesta vain tiivisteen.</li>
            <li><strong>IP-osoite:</strong> käytetään väärinkäytösten rajoittamiseen. Säilytämme osoitteen avaimellista tiivistettä välimuistissa lyhyen aikaa (minuuteista tuntiin). Osoitetta itseään ei tallenneta äänestyksiin tai ääniin. Palvelin voi kirjoittaa IP-osoitteita käyttölokeihin turvallisuuden ja vianetsinnän vuoksi, ja ne säilytetään lyhyen ajan.</li>
        </ul>
        <p>Emme kysy nimiä tai sähköpostiosoitteita emmekä käytä mainonta- tai markkinointiseurantaa.</p>

        <h2>Miksi käsittelemme tietoja</h2>
        <p>Tarjotaksemme pyytämäsi palvelun (äänestysten järjestäminen ja äänten laskenta) ja pitääksemme sen turvallisena. Käsittelyn peruste on oikeutettu etumme palvelun tarjoamiseen ja suojaamiseen.</p>

        <h2>Evästeet ja vastaava tallennus</h2>
        <p>Käytämme vain evästeitä, joita palvelun toiminta edellyttää: istuntoeväste, tietoturvaeväste (CSRF), kieliväste, äänestäjäeväste ja eväste, joka muistaa evästevalintasi. Teemavalintasi (vaalea tai tumma) tallennetaan selaimesi paikalliseen tallennustilaan, eikä sitä lähetetä meille. Koko lista on <a href="{{ route('cookies') }}">evästesivulla</a>.</p>

        <h2>Analytiikka</h2>
        <p>Aiomme mitata käyntejä itse ylläpidetyllä Plausible-palvelulla. Se ei käytä evästeitä, ei seuraa yksittäisiä henkilöitä eikä tallenna henkilötietoja.</p>

        <h2>Missä tiedot säilytetään ja ketkä niitä käsittelevät</h2>
        <p>Palvelu toimii Suomessa sijaitsevilla palvelimilla (Hetzner). Tiedot pysyvät EU:n alueella. Emme myy tietoja emmekä jaa niitä mainostajille.</p>

        <h2>Kuinka kauan tietoja säilytetään</h2>
        <p>Äänestys ja kaikki sen äänet poistetaan {{ config('polls.retention_days') }} päivää äänestyksen päättymisen jälkeen. Luoja voi poistaa äänestyksen aiemmin hallintasivulta. Välimuistin tiedot vanhenevat tunnin kuluessa.</p>

        <h2>Oikeutesi</h2>
        <p>Sinulla on oikeus tarkastaa, oikaista ja poistaa tietosi sekä vastustaa niiden käsittelyä. Koska äänet ovat nimettömiä, emme yleensä pysty selvittämään, mitkä äänet ovat sinun. Äänestyksen luoja voi poistaa äänestyksen milloin tahansa. Voit tehdä valituksen tietosuojavaltuutetulle (tietosuoja.fi).</p>
    </article>
</x-layouts.app>
