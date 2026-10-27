<x-layouts.app title="Usein kysytyt kysymykset">
    <article class="content">
        <h1>Usein kysytyt kysymykset</h1>
        <h2>Tarvitsenko tilin?</h2>
        <p>Et. Äänestyksen voi luoda rekisteröitymättä. Saat hallintalinkin, jolla hallitset äänestystä, sekä jakolinkin äänestäjille.</p>
        <h2>Hukkasin hallintalinkin. Voitteko palauttaa sen?</h2>
        <p>Emme voi. Tallennamme hallintalinkistä vain tiivisteen, emmekä voi palauttaa tai nollata sitä. Tallenna linkki äänestystä luodessasi.</p>
        <h2>Miten äänestystavat toimivat?</h2>
        <ul>
            <li><strong>Yksi valinta:</strong> jokainen äänestäjä valitsee yhden vaihtoehdon. Eniten ääniä saanut voittaa.</li>
            <li><strong>Useita valintoja:</strong> jokainen äänestäjä voi valita useita vaihtoehtoja, luoja voi asettaa enimmäismäärän. Eniten ääniä saanut voittaa.</li>
            <li><strong>Paremmuusjärjestys:</strong> jokainen äänestäjä järjestää vaihtoehdot paremmuusjärjestykseen. Jos 10 vaihtoehtoa järjestetään kokonaan, ensimmäinen sija saa 10 pistettä, toinen 9 ja niin edelleen yhteen asti. Top 5 -järjestyksessä äänestäjä valitsee ja järjestää viisi vaihtoehtoa, jotka saavat 5, 4, 3, 2 ja 1 pistettä, ja muut eivät saa pisteitä. Pisteet lasketaan yhteen ja eniten pisteitä saanut voittaa (Borda-pisteytys). Tasapisteet näytetään tasatuloksena.</li>
        </ul>
        <h2>Voiko joku äänestää kahdesti?</h2>
        <p>Selaimesi saa evästeen, joka tunnistaa äänesi. Yhdellä selaimella on siis yksi ääni per äänestys, ja sitä voi muuttaa äänestyksen ollessa auki. Tämä on kohtuullinen suoja, ei takuu: joka tyhjentää evästeet tai käyttää toista selainta, voi äänestää uudelleen.</p>
        <h2>Näkeekö luoja, miten äänestin?</h2>
        <p>Ei. Äänet tallennetaan ilman nimiä, ja luoja ja muut näkevät vain yhteenlasketut tulokset.</p>
        <h2>Miten aikavyöhykkeet toimivat?</h2>
        <p>Kun äänestyksessä on päiviä kellonajoilla, luoja valitsee aikavyöhykkeen. Äänestäjät näkevät ajat omassa aikavyöhykkeessään ja voivat vaihtaa sen toiseen.</p>
        <h2>Mitä äänestykselle tapahtuu myöhemmin?</h2>
        <p>Äänestys ja sen äänet poistetaan {{ config('polls.retention_days') }} päivää äänestyksen päättymisen jälkeen. Luoja voi poistaa äänestyksen aiemmin hallintasivulta.</p>
        <h2>Onko tämä ilmainen? Missä on koukku?</h2>
        <p>Palvelu on ilmainen, ilman mainoksia eikä tietoja myydä. Koukkua ei ole.</p>
    </article>
</x-layouts.app>
