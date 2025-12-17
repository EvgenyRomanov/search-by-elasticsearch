<?php

declare(strict_types=1);

namespace App\Domain\Entity;

/** @psalm-suppress PossiblyUnusedProperty */
final class Book
{
    public function __construct(
        public string $sku,
        public string $title,
        public string $category,
        public int $price,
        public array $stock
    ) {}
}
