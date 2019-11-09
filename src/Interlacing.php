<?php

namespace OwlyCode\Interlacing;

use OwlyCode\Interlacing\Plugin\AlterationInterface;
use OwlyCode\Interlacing\Plugin\ResolverInterface;
use Symfony\Component\Yaml\Yaml;

class Interlacing
{
    const DEFAULT_CAPTURE_PATTERN = '/{{(.+?)}}/';
    const STEP_PATTERN = '/^([[:alnum:]]+)(?:\((.+)\))?$/';

    private $grammar;
    private $resolvers;
    private $alterations;

    public static function fromString(string $string): self
    {
        return self::process(Yaml::parse($string));
    }

    public static function fromFile(string $path): self
    {
        return self::process(Yaml::parseFile($path));
    }

    private static function process(array $grammar): self
    {
        $instance = new self($grammar['content']);

        if ($grammar['std'] ?? true) {
            $instance->enableStdPlugins($grammar['locale'] ?? 'en');
        }

        foreach ($grammar['plugins'] ?? [] as $plugin) {
            $instance->addPlugin(new $plugin($instance));
        }

        return $instance;
    }

    public function __construct(array $grammar)
    {
        $this->grammar = $grammar;
        $this->resolvers = [];
        $this->alterations = [];
    }

    public function getGrammar(): array
    {
        return $this->grammar;
    }

    public function enableStdPlugins(string $locale): self
    {
        $this->addPlugin(new Plugin\Memory($this));
        $this->addPlugin(new Plugin\Capitalize());
        $this->addPlugin(new Plugin\Silence());

        if (!array_key_exists($locale, Plugin\Locale::LANGUAGES)) {
            throw new \RuntimeException(sprintf(
                'Unsupported locale "%s" for pluralization. Use one of %s',
                $locale,
                implode(', ', array_keys(Plugin\Locale::LANGUAGES))
            ));
        }

        $this->addPlugin(new Plugin\Locale(Plugin\Locale::LANGUAGES[$locale]));

        return $this;
    }

    public function addPlugin($plugin): self
    {
        if ($plugin instanceof AlterationInterface) {
            $this->addAlteration($plugin);
        }

        if ($plugin instanceof ResolverInterface) {
            $this->addResolver($plugin);
        }

        return $this;
    }

    public function addAlteration(AlterationInterface $alterations): self
    {
        foreach ($alterations->getAlterations() as $name => $alteration) {
            $this->alterations[$name] = $alteration;
        }

        return $this;
    }

    public function addResolver(ResolverInterface $resolver): self
    {
        $this->resolvers[] = $resolver;

        return $this;
    }

    public function parse(string $string): string
    {
        $output = $string;

        while ($transformed = $this->parseOne($output)) {
            $output = $transformed;
        }

        return $output;
    }

    public function parseOne(string $string): ?string
    {
        $output = $string;

        $found = preg_match(self::DEFAULT_CAPTURE_PATTERN, $string, $matches);

        if (!$found) {
            return null;
        }

        $source = $matches[0];
        $expr = $matches[1];

        $steps = array_map('trim', explode('|', $expr));

        $placeholder = array_shift($steps);
        $resolved = $this->resolve($placeholder);

        foreach ($steps as $step) {
            preg_match_all(self::STEP_PATTERN, $step, $tokens);

            $verb = $tokens[1][0];

            $args = str_getcsv($tokens[2][0]);

            if (!array_key_exists($verb, $this->alterations)) {
                throw new \RuntimeException(sprintf('Unknown alteration "%s".', $verb));
            }

            $resolved = $this->alterations[$verb]($placeholder, $resolved, $args);
        }

        $output = $this->replaceFirst($source, $resolved, $output);

        return $output;
    }

    public function resolve(string $name): string
    {
        foreach ($this->resolvers as $resolver) {
            if ($value = $resolver->resolve($name)) {
                return $value;
            }
        }

        if (!array_key_exists($name, $this->grammar)) {
            throw new \RuntimeException(sprintf('Unknown placeholder "%s".', $name));
        }

        $catalog = $this->grammar[$name];

        return $this->parse(is_string($catalog) ? $catalog : $this->pick($catalog));
    }

    public function pick(array $choices): string
    {
        return $choices[array_rand($choices)];
    }

    private function replaceFirst(string $from, string $to, string $content): string
    {
        $from = '/'.preg_quote($from, '/').'/';

        return preg_replace($from, $to, $content, 1);
    }
}
