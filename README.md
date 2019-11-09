## What is it?

It's a php alternative to [Tracery](https://tracery.io/) by [GalaxyKate](http://www.galaxykate.com/),
from which it was heavily inspired. It is used to generate procedural texts based on a grammar.

## How to use it?

Grab the phar at https://github.com/OwlyCode/interlacing/releases or install it as a dependency of your
project with composer: `composer req owlycode/interlacing`.

Firt declare a grammar:

```yaml
# grammar.yaml
content:
    root: "the {{ adjective }} {{ animal }}"
    animal: ["warthog", "hedgehog", "ocelot"]
    adjective: ["warty", "hoary", "oneiric"]
```

And then use it by running `interlacing.phar grammar.yaml` or inside your own code:

```php
use OwlyCode\Interlacing\Interlacing;

// index.php
$i = Interlacing::fromFile(__DIR__.'/grammar.yaml');

echo $i->resolve('root'); // Will output things like "the hoary ocelot".
```

The grammar file is composed of a list of placeholders and their possible associated values. You
can put placeholders inside content using the `{{ }}` delimiters. Each time interlacing encounters
a placeholder, it lookups for possible values from the grammar and picks one randomly.

Usually the `root` placeholder is used as an entrypoint.

## Using alterations and resolvers

You can apply alterations to your placeholders by appending them with a leading `|`. Alterations can be chained.

Resolvers are not manually applied, they just affect the way a placeholder is replaced by a value. Everytime a
placeholder is used, Interlacing runs every known resolvers and returns the value of the first one that gives a non-null result.
Otherwise, it lookups into the grammar for the possible values and picks one.

## Builtin alterations and resolvers

### Pluralize

Pluralize a word. You can change the locale inside the grammar (english by default):

```yaml
content:
    root: "{{ animal|s }}"
    name: ["cat", "dog", "mouse"] # cats, dogs, mice
```

```yaml
locale: fr
content:
    root: "{{ animal|s }}"
    name: ["chat", "chien", "cheval"] # chats, chiens, chevaux
```

### Capitalize

Capitalizes the first letter.

```yaml
content:
    proverb: "{{ animal|capitalize }} can eat {{ animal }}, as they say."
    name: ["the lion", "the mouse", "the cat", "the cow"]
```

Could produce: `The mouse can eat the cow, as they say.`

### Memory

Allows to dynamically build placeholders during the execution.

- store: stores the displayed placeholder value.
- storeAll: stores all possibles values of the placeholder.
- storeOthers: stores all possibles values of the placeholder except the one displayed.
- push: adds the displayed placeholder value to the already stored values.
- pop: removes the displayed memorized value from the memory.

Examples:

```yaml
content:
    root: ["We pick {{ value|store(selected_value) }} and we can show it later: {{ selected_value }}"]
    value: ['A', 'B', 'C']
```

```yaml
content:
    root: ["To make a cake you need {{ ingredient|storeOthers(i) }}, {{ i|pop }} and {{ i }}"]
    ingredient: ['eggs', 'flour', 'water']
```

```yaml
content:
    root: ["{{ duo }} went on an adventure. {{ adventurer|pop }} survived, but not {{ adventurer }}."]
    duo: ['{{ name|storeOthers(c)|push(adventurer) }} and {{ c|push(adventurer) }}']
    name: ['Alice', 'Peter', 'John', 'Frantz', 'Albert', 'Mark']
```

Beware: If you override a grammar based placeholder by a memory value, it will replace it entirely.

### Silence

Prevents a placeholder from rendering, often used with memory alterations: it allows to pair some results.

```yaml
content:
    root: ["Fishes are {{ level }} at {{ skill }}."]
    skill: [ "swimming{{ good|store(level)|silence }}", "flying{{ bad|store(level)|silence }}"]
    good: ["good", "skilled"]
    bad: ["bad", "unskilled"]
```

Will produce `Fishes are good at swimming.` or `Fishes are bad at flying.` but never `Fishes are good at flying.`.

## Creating your own alterations and resolvers

You can create your own alterations by implementing the `OwlyCode\Interlacing\Plugin\AlterationInterface` interface:

```php
namespace Custom\Plugin;

class Prepend implements AlterationInterface
{
    // An alteration always receive the placeholder name, the actual resolved value and the args provided to the alteration
    public function prepend(string $placeholder, string $input, array $args): string
    {
        return $args[0] . $input;
    }

    // Returns the list of alterations by name.
    public function getAlterations(): array
    {
        return [
            'prepend' => [$this, 'prepend'], // Usage: {{ placeholder|prepend("prefix") }}
        ];
    }
}
```

And you can create a resolver by implementing the `OwlyCode\Interlacing\Plugin\ResolverInterface` interface:

```php
namespace Custom\Plugin;

class Now implements ResolverInterface
{
    public function resolve($name): ?string
    {
        if ($name === 'now') {
            return (new \Datetime())->format('Y-m-d H:i:s');
        }

        return null;
    }
}
```

Now let's use our previously declared alteration and resolver:

```yaml
plugins:
    - Custom\Plugin\Now
    - Custom\Plugin\Prepend
content:
    root: "{{ now|prepend("time: ") }}"
```

This would display: `time: 2019-11-08 22:44:00`, where the date and time are set to now.
