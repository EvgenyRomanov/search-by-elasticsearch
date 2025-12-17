<?php

declare(strict_types=1);

namespace App\Application\UseCase;

use App\Domain\Repository\BookRepository;

final readonly class SearchByTitleCategoryPrice
{
    public function __construct(private BookRepository $bookRepository) {}

    public function __invoke(string $title, string $category, string $price): array
    {
        return $this->bookRepository->searchByTitleCategoryPrice($title, $category, $price);
    }
}
