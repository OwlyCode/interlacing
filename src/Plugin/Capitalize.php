<?php

namespace OwlyCode\Interlacing\Plugin;

class Capitalize implements AlterationInterface
{
    public function alter(string $placeholder, string $input, array $args): string
    {
        return ucfirst(strtolower($input));
    }

    public function getAlterations(): array
    {
        return [
            'capitalize' => [$this, 'alter'],
        ];
    }
}
