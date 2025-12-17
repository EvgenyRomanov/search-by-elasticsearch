<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository;

use App\Domain\Entity\Book;
use App\Domain\Repository\BookRepository;
use Elastic\Elasticsearch\Client;

final readonly class ElasticSearchBookRepository implements BookRepository
{
    /** @var array<string, string> */
    private const array OPERATIONS = [
        '>=' => 'gte',
        '<=' => 'lte',
    ];

    public function __construct(public Client $elasticClient, private string $index) {}

    /** @return array<Book> */
    public function searchByTitle(string $title): array
    {
        return $this->search(self::queryByTitle($title));
    }

    /** @return array<Book> */
    public function searchByTitleCategory(string $title, string $category): array
    {
        return $this->search(self::queryByTitleCategory($title, $category));
    }

    /** @return array<Book> */
    public function searchByTitleCategoryPrice(string $title, string $category, string $price): array
    {
        return $this->search(self::queryByTitleCategoryPrice($title, $category, $price));
    }

    /** @return array<Book> */
    public function searchByTitleCategoryPriceAvailability(string $title, string $category, string $price): array
    {
        return $this->search(self::queryByTitleCategoryPriceAvailability($title, $category, $price));
    }

    /** @return array<Book> */
    private function search(array $query): array
    {
        $params = [
            'index' => $this->index,
            'body' => [
                'query' => $query,
            ],
        ];

        /**
         * @psalm-suppress MixedAssignment
         * @psalm-suppress PossiblyUndefinedMethod
         */
        $results = $this->elasticClient->search($params)->asArray();
        /**
         * @psalm-suppress MissingClosureReturnType
         * @psalm-suppress MixedArrayAccess
         * @psalm-suppress MissingClosureParamType
         * @psalm-suppress MixedArgument
         */
        $results = !empty($results['hits']['hits']) ? array_map(static fn($item) => $item['_source'], $results['hits']['hits']) : [];

        return $this->modifyResult($results);
    }

    /** @return array<Book> */
    private function modifyResult(array $result): array
    {
        /** @var array{sku: string, title: string, category: string, price: int, stock: array} $item */
        foreach ($result as &$item) {
            $book = new Book($item['sku'], $item['title'], $item['category'], $item['price'], $item['stock']);
            $item = $book;
        }

        /** @var array<Book> */
        return $result;
    }

    private static function queryByTitle(string $title): array
    {
        return [
            "match" => [
                "title" => [
                    "query" => $title,
                    "fuzziness" => "auto",
                ],
            ],
        ];
    }

    private static function queryByTitleCategory(string $title, string $category): array
    {
        return [
            "bool" => [
                "must" => [
                    self::queryByTitle($title),
                ],
                "filter" => [
                    "term" => [
                        "category" => $category,
                    ],
                ],
            ],
        ];
    }

    private static function queryByTitleCategoryPrice(string $title, string $category, string $price): array
    {
        $operation = self::OPERATIONS[substr($price, 0, 2)];

        return [
            "bool" => [
                "must" => [
                    self::queryByTitle($title),
                ],
                "filter" => [
                    [
                        "term" => [
                            "category" => $category,
                        ],
                    ],
                    [
                        "range" => [
                            "price" => [
                                $operation => substr($price, 2),
                            ],
                        ],
                    ],

                ],
            ],
        ];
    }

    private static function queryByTitleCategoryPriceAvailability(string $title, string $category, string $price): array
    {
        /** @var array{bool: array{filter: array}} $result */
        $result = self::queryByTitleCategoryPrice($title, $category, $price);
        $result['bool']['filter'][] = [
            "nested" => [
                "path" => "stock",
                "query" => [
                    "range" => [
                        "stock.stock" => [
                            "gte" => 1,
                        ],
                    ],
                ],
            ],
        ];

        return $result;
    }
}
