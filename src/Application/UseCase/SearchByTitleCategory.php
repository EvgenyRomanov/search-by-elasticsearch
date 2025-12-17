<?php

declare(strict_types=1);

namespace App\Application\UseCase;

use App\Domain\Repository\BookRepository;

final readonly class SearchByTitleCategory
{
    public function __construct(private BookRepository $bookRepository) {}

    public function __invoke(string $title, string $category): array
    {
        return $this->bookRepository->searchByTitleCategory($title, $category);
    }
}
