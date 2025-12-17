<?php

use App\Application\UseCase\SearchByTitle;
use App\Application\UseCase\SearchByTitleCategory;
use App\Application\UseCase\SearchByTitleCategoryPrice;
use App\Application\UseCase\SearchByTitleCategoryPriceAvailability;
use App\Infrastructure\Repository\ElasticSearchBookRepository;
use Elastic\Elasticsearch\ClientBuilder;
use Elastic\Elasticsearch\Response\Elasticsearch;

/** @psalm-suppress MissingFile */
require __DIR__ . '/../vendor/autoload.php';

try {
    /** @var string $data */
    $data = getenv('BULK_FILE');
    /** @var string $url */
    $url = getenv('ES_URL');
    /** @var string $user */
    $user = getenv('ES_USER');
    /** @var string $password */
    $password = getenv('ES_PASSWORD');

    /**
     * @var array{body?: array<array-key, mixed>|string, error_trace?: bool, filter_path?: array<array-key, string>|string, human?: bool, index: string, master_timeout?: int|string, pretty?: bool, source?: string, timeout?: int|string, wait_for_active_shards?: string} $params
     * @psalm-suppress MissingFile
     */
    $params = require __DIR__ . "/../config/config_index_es.php";
    $client = ClientBuilder::create()
        ->setHosts([$url])
        ->setBasicAuthentication($user, $password)
        ->build();

    /** @var array{allow_no_indices?: bool, error_trace?: bool, expand_wildcards?: string, filter_path?: array<array-key, string>|string, flat_settings?: bool, human?: bool, ignore_unavailable?: bool, include_defaults?: bool, index: array<array-key, string>|string, local?: bool, pretty?: bool, source?: string} $existsParams */
    $existsParams = ['index' => $params['index']];
    /** @var Elasticsearch $response */
    $response = $client->indices()->exists($existsParams);

    // Создаем индекс, если его еще нет
    if ($response->getStatusCode() != 200) {
        $client->indices()->create($params);
        /** @psalm-suppress ForbiddenCode */
        shell_exec("curl --location --request POST '{$url}/_bulk' --header 'Content-Type: application/json' --data-binary '@data/{$data}'");
    }

    $repository = new ElasticSearchBookRepository($client, $params['index']);

    // Поиск по title
    $useCase = new SearchByTitle($repository);
    print_r($useCase("Довакин"));
    echo PHP_EOL;

    // Поиск по title и строгому соответствую категории category
    $useCase = new SearchByTitleCategory($repository);
    print_r($useCase("Довакин", "Любовный роман"));
    echo PHP_EOL;

    // Поиск по title, строгому соответствую категории category и ценой <=|>= указанной
    $useCase = new SearchByTitleCategoryPrice($repository);
    print_r($useCase("Довакин", "Любовный роман", ">=9700"));
    echo PHP_EOL;

    // Товар должен быть в наличии
    $useCase = new SearchByTitleCategoryPriceAvailability($repository);
    print_r($useCase("Штирлиц", "Исторический роман", ">=700") );
    echo PHP_EOL;

} catch (Throwable $th) {
    print_r($th->getMessage());
    echo PHP_EOL;
}
