<?php

namespace OwlyCode\Interlacing\Plugin;

interface ResolutionInterface
{
    public function resolve($name): ?string;
}
