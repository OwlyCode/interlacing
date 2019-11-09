<?php

namespace OwlyCode\Interlacing\Plugin;

use OwlyCode\Interlacing\Interlacing;

class Memory implements AlterationInterface, ResolverInterface
{
    private $memory;

    public function __construct(Interlacing $interlacing)
    {
        $this->memory = [];
        $this->interlacing = $interlacing;
    }

    public function resolve($name): ?string
    {
        $memory = $this->memory[$name] ?? null;

        if (is_array($memory)) {
            return $this->interlacing->pick($memory);
        }

        return $memory;
    }

    public function store(string $placeholder, string $input, array $args): string
    {
        $this->memory[$args[0]] = $input;

        return $input;
    }

    public function storeOthers(string $placeholder, string $input, array $args): string
    {
        $this->storeAll($placeholder, $input, $args);
        $this->pop($placeholder, $input, $args);

        return $input;
    }

    public function storeAll(string $placeholder, string $input, array $args): string
    {
        $this->memory[$args[0]] = $this->interlacing->getGrammar()[$placeholder];

        return $input;
    }

    public function push(string $placeholder, string $input, array $args): string
    {
        $this->memory[$args[0]][] = $input;

        $this->memory[$args[0]] = array_unique($this->memory[$args[0]]);

        return $input;
    }

    public function pop(string $placeholder, string $input, array $args): string
    {
        $location = $args[0] ? $args[0] : $placeholder;

        if (!isset($this->memory[$location])) {
            throw new \RuntimeException(sprintf('Cannot pop from memory "%s" : empty.', $location));
        }

        $this->memory[$location] = array_filter($this->memory[$location], function ($value) use ($input) {
            return $value !== $input;
        });

        return $input;
    }

    public function getAlterations(): array
    {
        return [
            'store' => [$this, 'store'],
            'storeAll' => [$this, 'storeAll'],
            'storeOthers' => [$this, 'storeOthers'],
            'push' => [$this, 'push'],
            'pop' => [$this, 'pop'],
        ];
    }

    public function reset()
    {
        $this->memory = [];
    }
}
