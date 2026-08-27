<?php

/*
 |  Demo fotograf konu kurallari.
 |
 |  Arka plan filtresi "temiz cekim mi?" sorusunu cevapliyor ama "dogru esya mi?"
 |  sorusunu cevaplamiyordu: "table white background" aramasi TUZLUK getirdi
 |  ("table salt"). Bu yuzden her tur icin dosya basliginda BULUNMASI gereken ve
 |  KESINLIKLE bulunmamasi gereken kelimeler tanimli.
 */

return [
    'sofa' => [
        'gerekli' => ['sofa', 'couch', 'settee', 'divan', 'canape'],
        'yasak' => ['sofa bed frame only', 'car', 'stadium'],
    ],
    'armchair' => [
        'gerekli' => ['armchair', 'arm chair', 'easy chair', 'lounge chair', 'fauteuil', 'sessel'],
        'yasak' => ['wheelchair', 'car'],
    ],
    'chair' => [
        'gerekli' => ['chair', 'stuhl', 'chaise'],
        'yasak' => ['wheelchair', 'chairman', 'chairlift', 'electric chair', 'sedan chair', 'lifeguard'],
    ],
    'office_chair' => [
        'gerekli' => ['office chair', 'desk chair', 'swivel', 'task chair', 'buerostuhl', 'burostuhl', 'chair', 'seating'],
        'yasak' => ['wheelchair'],
    ],
    'coffee_table' => [
        'gerekli' => ['coffee table', 'side table', 'occasional table', 'beistelltisch', 'end table'],
        'yasak' => ['salt', 'pepper', 'shaker', 'periodic', 'water table', 'coffee cup', 'coffee bean'],
    ],
    'dining_table' => [
        'gerekli' => ['table'],
        'yasak' => [
            'salt', 'pepper', 'shaker', 'periodic', 'water table', 'tennis', 'billiard', 'pool table',
            'multiplication', 'timetable', 'turntable', 'vegetable', 'tablet', 'tableau', 'tablecloth',
            'table of', 'round table conference', 'operating table', 'drafting',
        ],
    ],
    'tv_unit' => [
        'gerekli' => ['sideboard', 'credenza', 'cabinet', 'lowboard', 'anrichte', 'buffet'],
        'yasak' => ['bathroom', 'kitchen sink', 'filing', 'electrical', 'fuse', 'medicine', 'luau', 'designs', 'guide'],
    ],
    'bed' => [
        'gerekli' => ['bed', 'bett', 'lit '],
        'yasak' => ['bedrock', 'flower bed', 'riverbed', 'bedding plant', 'seabed', 'bedroom slipper', 'hospital'],
    ],
    'wardrobe' => [
        'gerekli' => ['wardrobe', 'armoire', 'kleiderschrank', 'cupboard', 'closet'],
        'yasak' => ['water closet', 'kitchen'],
    ],
    'nightstand' => [
        'gerekli' => ['nightstand', 'bedside', 'night table', 'nachttisch'],
        'yasak' => [],
    ],
    'dresser' => [
        'gerekli' => ['chest of drawers', 'dresser', 'commode', 'kommode', 'drawer'],
        'yasak' => ['salad dresser', 'window dresser', 'kitchen'],
    ],
    'console' => [
        'gerekli' => ['console table', 'hall table', 'konsole', 'console'],
        'yasak' => ['game', 'nintendo', 'playstation', 'xbox', 'mixing', 'organ', 'sega', 'atari', 'controller'],
    ],
    'display_cabinet' => [
        'gerekli' => ['display cabinet', 'vitrine', 'showcase', 'china cabinet', 'glass cabinet', 'buffet', 'cabinet'],
        'yasak' => ['museum exhibit', 'shop window', 'drawing', 'elevation', 'design', 'engraving'],
    ],
    'desk' => [
        'gerekli' => ['desk', 'schreibtisch', 'bureau', 'writing table'],
        'yasak' => ['desktop computer', 'help desk', 'reception'],
    ],
    'bookcase' => [
        'gerekli' => ['bookcase', 'bookshelf', 'shelving', 'regal', 'etagere', 'bibliotheque'],
        'yasak' => ['library interior', 'bookstore'],
    ],
    'lamp' => [
        'gerekli' => ['lamp', 'lampe', 'luminaire', 'light fixture'],
        'yasak' => ['street', 'oil lamp', 'lava lamp', 'headlamp', 'lamp post', 'miner', 'bulb only', 'signal'],
    ],
    'chandelier' => [
        'gerekli' => ['chandelier', 'pendant', 'ceiling lamp', 'kronleuchter', 'luster'],
        'yasak' => ['pendant necklace', 'jewel'],
    ],
    'rug' => [
        'gerekli' => ['rug', 'carpet', 'teppich', 'kilim', 'tapis'],
        'yasak' => ['rugby', 'carpet python', 'red carpet event'],
    ],
    'cushion' => [
        'gerekli' => ['cushion', 'pillow', 'kissen', 'coussin'],
        'yasak' => ['air cushion', 'whoopee', 'seat belt'],
    ],
    'curtain' => [
        'gerekli' => ['curtain', 'drape', 'vorhang', 'rideau'],
        'yasak' => ['iron curtain', 'theatre', 'shower', 'air curtain'],
    ],
    'mirror' => [
        'gerekli' => ['mirror', 'spiegel', 'miroir'],
        'yasak' => ['rear-view', 'rearview', 'car', 'carp', 'fish', 'telescope', 'lake', 'galaxy', 'neuron'],
    ],
    'artwork' => [
        'gerekli' => ['frame', 'rahmen', 'cadre', 'picture'],
        'yasak' => ['bicycle', 'bike', 'window frame', 'door frame', 'timber frame', 'a-frame', 'frame house'],
    ],
    'shelf' => [
        'gerekli' => ['shelf', 'shelves', 'regal', 'etagere'],
        'yasak' => ['continental shelf', 'ice shelf', 'shop shelf', 'supermarket'],
    ],
];
