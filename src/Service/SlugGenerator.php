<?php

namespace App\Service;

use Symfony\Component\String\Slugger\SluggerInterface;

class SlugGenerator
{
    public function __construct(
        private readonly SluggerInterface $slugger,
    ) {}

    public function generate(string $text): string
    {
        return strtolower($this->slugger->slug($text)->toString());
    }
}
