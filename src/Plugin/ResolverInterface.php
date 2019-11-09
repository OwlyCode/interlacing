<?php

namespace OwlyCode\Interlacing\Plugin;

interface ResolverInterface
{
    public function resolve($name): ?string;
}
