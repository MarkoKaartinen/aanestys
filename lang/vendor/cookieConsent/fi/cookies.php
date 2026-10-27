<?php
return [
    'title' => 'Käytämme evästeitä',
    'intro' => 'Tämä sivusto käyttää evästeitä, jotta palvelu toimii ja käyttökokemus on sujuva.',
    'link' => 'Lue lisää <a href=":url">evästekäytännöstämme</a>.',

    'essentials' => 'Vain välttämättömät',
    'all' => 'Hyväksy kaikki',
    'customize' => 'Mukauta',
    'manage' => 'Hallitse evästeitä',
    'details' => [
        'more' => 'Lisätietoja',
        'less' => 'Vähemmän tietoja',
    ],
    'save' => 'Tallenna asetukset',
    'cookie' => 'Eväste',
    'purpose' => 'Tarkoitus',
    'duration' => 'Kesto',
    'year' => 'Vuosi|Vuotta',
    'day' => 'Päivä|Päivää',
    'hour' => 'Tunti|Tuntia',
    'minute' => 'Minuutti|Minuuttia',

    'categories' => [
        'essentials' => [
            'title' => 'Välttämättömät evästeet',
            'description' => 'Osa evästeistä on välttämättömiä, jotta sivut toimivat. Siksi ne eivät vaadi suostumustasi.',
        ],
        'analytics' => [
            'title' => 'Analytiikkaevästeet',
            'description' => 'Näitä käytetään palvelun kehittämiseen sen mukaan, miten sivustoa käytetään.',
        ],
        'optional' => [
            'title' => 'Valinnaiset evästeet',
            'description' => 'Nämä evästeet mahdollistavat lisätoimintoja, mutta sivustoa voi käyttää myös ilman niitä.',
        ],
    ],

    'defaults' => [
        'consent' => 'Tallentaa evästevalintasi.',
        'session' => 'Tunnistaa selaussessiosi.',
        'locale' => 'Muistaa valitsemasi kielen.',
        'csrf' => 'Suojaa sinua ja palvelua väärennetyiltä pyynnöiltä (CSRF).',
        '_ga' => 'Google Analyticsin pääeväste, erottaa kävijät toisistaan.',
        '_ga_ID' => 'Google Analytics käyttää tätä istunnon tilan säilyttämiseen.',
        '_gid' => 'Google Analytics käyttää tätä käyttäjän tunnistamiseen.',
        '_gat' => 'Google Analytics käyttää tätä pyyntöjen rajoittamiseen.',
    ],
];
