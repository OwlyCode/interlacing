<?php

namespace OwlyCode\Interlacing\Plugin;

use Doctrine\Inflector\InflectorFactory;
use Doctrine\Inflector\Language;

class Locale implements AlterationInterface
{
    const LANGUAGES = [
        'fr' => Language::FRENCH,
        'en' => Language::ENGLISH,
        'es' => Language::SPANISH,
        'pt' => Language::PORTUGUESE,
        'tr' => Language::TURKISH,
        'nb' => Language::NORWEGIAN_BOKMAL,
    ];

    private $inflector;

    public function __construct(string $language)
    {
        $inflectorFactory = new InflectorFactory();

        $this->inflector = $inflectorFactory($language);
    }

    public function pluralize(string $placeholder, string $input, array $args): string
    {
        return $this->inflector->pluralize($input);
    }

    public function getAlterations(): array
    {
        return [
            's' => [$this, 'pluralize'],
        ];
    }
}
