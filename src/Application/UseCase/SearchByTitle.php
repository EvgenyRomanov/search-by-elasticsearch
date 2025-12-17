<?php

declare(strict_types=1);

namespace App\Application\UseCase;

use App\Domain\Repository\BookRepository;

final readonly class SearchByTitle
{
    public function __construct(private BookRepository $bookRepository) {}

    public function __invoke(string $title): array
    {
        return $this->bookRepository->searchByTitle($title);
    }
}
