<?php

namespace OwlyCode\Interlacing;

use OwlyCode\Interlacing\Plugin\AlterationInterface;
use OwlyCode\Interlacing\Plugin\Capitalize;
use OwlyCode\Interlacing\Plugin\Memory;
use OwlyCode\Interlacing\Plugin\ResolutionInterface;
use OwlyCode\Interlacing\Plugin\Silence;

class Parser
{
    const DEFAULT_CAPTURE_PATTERN = '/{{(.+?)}}/';
    const STEP_PATTERN = '/^([[:alnum:]]+)(?:\((.+)\))?$/';

    private $grammar;
    private $resolutions;
    private $alterations;

    public function __construct(array $grammar)
    {
        $this->grammar = $grammar;
        $this->resolutions = [];
        $this->alterations = [];
    }

    public function getGrammar(): array
    {
        return $this->grammar;
    }

    public function enableStdPlugins()
    {
        $this->addAlterationAndResolution(new Memory($this));
        $this->addAlteration(new Capitalize());
        $this->addAlteration(new Silence());
    }

    /**
     * @param AlterationInterface|ResolutionInterface $alterationAndResolution
     */
    public function addAlterationAndResolution($alterationAndResolution)
    {
        $this->addAlteration($alterationAndResolution);
        $this->addResolution($alterationAndResolution);
    }

    public function addAlteration(AlterationInterface $alterations)
    {
        foreach ($alterations->getAlterations() as $name => $alteration) {
            $this->alterations[$name]= $alteration;
        }
    }

    public function addResolution(ResolutionInterface $resolution)
    {
        $this->resolutions[]= $resolution;
    }

    public function parse(string $string): string
    {
        $output = $string;

        while ($transformed = $this->parseOne($output)) {
            $output = $transformed;
        }

        return $output;
    }

    public function parseOne(string $string)
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
            $args = array_map('trim', explode(',', $tokens[2][0]));

            $resolved = $this->alterations[$verb]($placeholder, $resolved, $args);
        }

        $output = $this->replaceFirst($source, $resolved, $output);

        return $output;
    }

    public function resolve(string $name): string
    {
        foreach ($this->resolutions as $resolution) {
            if ($value = $resolution->resolve($name)) {
                return $value;
            }
        }

        $catalog = $this->grammar[$name];

        return $this->parse($this->pick($catalog));
    }

    public function pick(array $choices): string
    {
        return $choices[array_rand($choices)];
    }

    private function replaceFirst(string $from, string $to, string $content)
    {
        $from = '/'.preg_quote($from, '/').'/';

        return preg_replace($from, $to, $content, 1);
    }
}
