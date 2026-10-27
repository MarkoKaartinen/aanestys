<?php

/*
 * Finnish validation messages for the rules the app uses. Missing keys fall
 * back to the English messages that ship with the framework.
 */
return [
    'array' => 'Kentän :attribute on oltava lista.',
    'boolean' => 'Kentän :attribute on oltava tosi tai epätosi.',
    'date' => 'Kentän :attribute on oltava kelvollinen päivämäärä.',
    'date_format' => 'Kentän :attribute on oltava muodossa :format.',
    'distinct' => 'Kentässä :attribute on kaksoisarvo.',
    'enum' => 'Valittu :attribute ei kelpaa.',
    'integer' => 'Kentän :attribute on oltava kokonaisluku.',
    'required' => 'Kenttä :attribute on pakollinen.',
    'string' => 'Kentän :attribute on oltava teksti.',
    'timezone' => 'Kentän :attribute on oltava kelvollinen aikavyöhyke.',

    'max' => [
        'array' => 'Kentässä :attribute saa olla enintään :max kohtaa.',
        'numeric' => 'Kentän :attribute arvo saa olla enintään :max.',
        'string' => 'Kentän :attribute pituus saa olla enintään :max merkkiä.',
    ],
    'min' => [
        'array' => 'Kentässä :attribute on oltava vähintään :min kohtaa.',
        'numeric' => 'Kentän :attribute arvon on oltava vähintään :min.',
        'string' => 'Kentän :attribute pituuden on oltava vähintään :min merkkiä.',
    ],
];
