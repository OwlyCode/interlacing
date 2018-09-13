<?php

namespace OwlyCode\Interlacing\Plugin;

class Silence implements AlterationInterface
{
    public function alter(string $placeholder, string $input, array $args): string
    {
        return '';
    }

    public function getAlterations(): array
    {
        return [
            'silence' => [$this, 'alter'],
        ];
    }
}
