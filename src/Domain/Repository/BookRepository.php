<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Entity\Book;

interface BookRepository
{
    /** @return array<Book> */
    public function searchByTitle(string $title): array;

    /** @return array<Book> */
    public function searchByTitleCategory(string $title, string $category): array;

    /** @return array<Book> */
    public function searchByTitleCategoryPrice(string $title, string $category, string $price): array;

    /** @return array<Book> */
    public function searchByTitleCategoryPriceAvailability(string $title, string $category, string $price): array;
}
