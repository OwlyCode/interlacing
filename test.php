<?php

use OwlyCode\Interlacing\Parser;

require __DIR__.'/vendor/autoload.php';

$grammar = [
    "root" => ['{{ landscape_root }}', '{{ animals_root }}', '{{ cake_root }}', '{{ adventurers_root }}'],

    "landscape_root" => ["{{ path|store(myPlace)|silence }}{{line}}"],
    "line" => ["{{ mood|capitalize }} and {{ mood }}, the {{ myPlace }} was {{ mood }} with {{ substance }}", "{{ nearby|capitalize }} {{ myPlace }} {{ move }} through the {{ path }}, filling me with {{ substance }}"],
    "nearby" => ["beyond the {{ path }}", "far away", "ahead", "behind me"],
    "substance" => ["light", "reflections", "mist", "shadow", "darkness", "brightness", "gaiety", "merriment"],
    "mood" => ["overcast", "alight", "clear", "darkened", "blue", "shadowed", "illuminated", "silver", "cool", "warm", "summer-warmed"],
    "path" => ["stream", "brook", "path", "ravine", "forest", "fence", "stone wall"],
    "move" => ["spiral", "twirl", "curl", "dance", "twine", "weave", "meander", "wander", "flow"],

    'animals_root' => ["{{ animal|store(hero)|capitalize }} was {{ level }} at {{ skill }}. {{ statement }} {{ hero }} died."],
    'animal' => [ "the dog", "the cat"],
    'level' => [ "good{{ appreciation|store(statement)|silence }}", "bad{{ depreciation|store(statement)|silence }}"],
    'skill' => [ "climbing", "swimming" ],
    'appreciation' => ["It's a shame that", "Alas,"],
    'depreciation' => ["Luckily,", "We are all relieved"],

    'cake_root' => ["To make a cake you need {{ ingredient|storeOthers(i) }}, {{ i|pop }} and {{ i }}"],
    'ingredient' => ['eggs', 'flour', 'water'],

    'adventurers_root' => ["{{ duo }} went on an adventure. {{ adventurer|pop }} survived, but not {{ adventurer }}."],
    'duo' => ['{{ name|storeOthers(c)|push(adventurer) }} and {{ c|push(adventurer) }}'],
    'name' => ['Alice', 'Peter', 'John', 'Frantz', 'Albert', 'Mark'],
];

for ($i=0; $i < 10; $i++) {
    $p = new Parser($grammar);

    $p->enableStdPlugins();

    echo $p->resolve('root') . "\n";
}
