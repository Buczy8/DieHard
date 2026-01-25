<?php

namespace App\Annotation;

use atribute;

#[\Attribute(\Attribute::TARGET_METHOD)]
class AllowedMethods
{
    public array $methods;

    public function __construct(array $methods)
    {
        $this->methods = $methods;
    }
}