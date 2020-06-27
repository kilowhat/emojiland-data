<?php

$locales = [
    'en',
    'fr',
    'de',
];

$packs = [
    [
        'key' => 'people',
        'icon' => 'fas fa-smile',
        'groups' => [0, 1],
    ],
    [
        'key' => 'nature',
        'icon' => 'fas fa-cat',
        'groups' => [3],
    ],
    [
        'key' => 'food',
        'icon' => 'fas fa-hamburger',
        'groups' => [4],
    ],
    [
        'key' => 'activities',
        'icon' => 'fas fa-car',
        'groups' => [6],
    ],
    [
        'key' => 'travel',
        'icon' => 'fas fa-table-tennis',
        'groups' => [5],
    ],
    [
        'key' => 'objects',
        'icon' => 'fas fa-lightbulb',
        'groups' => [7],
    ],
    [
        'key' => 'symbols',
        'icon' => 'fas fa-heart',
        'groups' => [8],
    ],
    [
        'key' => 'flags',
        'icon' => 'fas fa-flag',
        'groups' => [9],
    ],
];

$englishKeywords = [];

foreach ($locales as $locale) {
    echo "Compiling emojis for locale $locale" . PHP_EOL;

    $data = json_decode(file_get_contents(__DIR__ . "/node_modules/emojibase-data/$locale/data.json"), true);

    foreach ([true, false] as $withEnglishKeywords) {
        // Don't generate english with english
        if ($locale === 'en' && $withEnglishKeywords) {
            continue;
        }

        $out = array_map(function (array $pack) use ($data, $locale, &$englishKeywords, $withEnglishKeywords) {
            $emojis = array_values(array_filter($data, function (array $emoji) use ($pack) {
                return in_array($emoji['group'], $pack['groups']);
            }));

            return [
                'key' => $pack['key'],
                'icon' => $pack['icon'],
                'emojis' => array_map(function (array $emoji) use ($locale, &$englishKeywords, $withEnglishKeywords) {
                    $name = $emoji['annotation'];

                    // Remove the "flag:" prefix in the name of every flag
                    if (strpos($name, 'flag: ') === 0) {
                        $name = substr($name, 6);
                    }
                    if (strpos($name, 'drapeau : ') === 0) { // includes nbsp
                        $name = substr($name, 10);
                    }
                    if (strpos($name, 'Flagge: ') === 0) {
                        $name = substr($name, 8);
                    }

                    $keywords = $emoji['tags'];

                    if ($locale === 'en') {
                        $englishKeywords[$emoji['emoji']] = array_merge($keywords, [$name]);
                    }

                    if ($withEnglishKeywords && array_key_exists($emoji['emoji'], $englishKeywords)) {
                        $keywords = array_merge($keywords, $englishKeywords[$emoji['emoji']]);
                    }

                    $keywords = array_filter($keywords, function ($tag) use ($name) {
                        // Remove unwanted tags and remove any tag that's identical to the name as the name will be search as well
                        return !in_array($tag, [
                                'flag',
                                'drapeau',
                                'flagge',
                            ]) && $tag !== $name;
                    });

                    $keywords = array_unique($keywords);

                    $data = [
                        // In an effort to reduce file size, names are shorted to a single character
                        // n = name
                        'n' => $name,
                        // k = keywords
                        'k' => implode("\t", $keywords),
                    ];

                    // For skins, we don't add any i or h property, and put all skin options in an s property
                    // The default i and h will be assigned on the fly by the extension
                    if (array_key_exists('skins', $emoji)) {
                        // s = skins
                        $data['s'] = array_merge([[
                            'i' => $emoji['emoji'],
                            'h' => strtolower($emoji['hexcode']),
                        ]], array_map(function ($skinEmoji) {
                            return [
                                'i' => $skinEmoji['emoji'],
                                'h' => strtolower($skinEmoji['hexcode']),
                            ];
                        }, $emoji['skins']));
                    } else {
                        $data += [
                            // i = the content to insert
                            'i' => $emoji['emoji'],
                            // h = hexcode for CDN url
                            'h' => strtolower($emoji['hexcode']),
                        ];
                    }

                    return $data;
                }, $emojis),
            ];
        }, $packs);

        file_put_contents(__DIR__ . "/dist/$locale" . ($withEnglishKeywords ? '-with-en' : '') . '.json', json_encode($out, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }
}

echo 'Done' . PHP_EOL;
