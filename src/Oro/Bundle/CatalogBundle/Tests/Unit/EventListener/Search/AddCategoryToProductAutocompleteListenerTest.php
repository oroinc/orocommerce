<?php

namespace Oro\Bundle\CatalogBundle\Tests\Unit\EventListener\Search;

use Oro\Bundle\CatalogBundle\EventListener\Search\AddCategoryToProductAutocompleteListener;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ProductBundle\Event\ProcessAutocompleteDataEvent;
use Oro\Bundle\ProductBundle\Event\ProcessAutocompleteQueryEvent;
use Oro\Bundle\SearchBundle\Engine\EngineInterface;
use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\SearchBundle\Query\Result;
use Oro\Bundle\UIBundle\Twig\HtmlTagExtension;
use Oro\Bundle\WebsiteSearchBundle\Query\WebsiteSearchQuery;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class AddCategoryToProductAutocompleteListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var UrlGeneratorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $urlGenerator;

    /** @var HtmlTagExtension|\PHPUnit\Framework\MockObject\MockObject */
    private $htmlTagExtension;

    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    private $configManager;

    /** @var AddCategoryToProductAutocompleteListener */
    private $listener;

    protected function setUp(): void
    {
        $this->urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $this->htmlTagExtension = $this->createMock(HtmlTagExtension::class);
        $this->configManager = $this->createMock(ConfigManager::class);

        $this->listener = new AddCategoryToProductAutocompleteListener(
            $this->urlGenerator,
            $this->htmlTagExtension,
            $this->configManager
        );
    }

    /**
     * @dataProvider onProcessAutocompleteQueryDataProvider
     */
    public function testOnProcessAutocompleteQuery($numberOfCategories, array $aggregations)
    {
        $engine = $this->createMock(EngineInterface::class);
        $query = new WebsiteSearchQuery($engine, new Query());

        $this->configManager->expects($this->once())
            ->method('get')
            ->with('oro_catalog.search_autocomplete_max_categories')
            ->willReturn($numberOfCategories);

        $event = new ProcessAutocompleteQueryEvent($query, 'request');
        $this->listener->onProcessAutocompleteQuery($event);

        $this->assertEquals($aggregations, $event->getQuery()->getAggregations());
    }

    public function onProcessAutocompleteQueryDataProvider(): array
    {
        return [
            'zero categories' => [
                'numberOfCategories' => 0,
                'aggregations' => []
            ],
            'five categories' => [
                'numberOfCategories' => 5,
                'aggregations' => [
                    'category' => [
                        'field' => 'text.category_id_with_parent_categories_LOCALIZATION_ID',
                        'function' => Query::AGGREGATE_FUNCTION_COUNT,
                        'parameters' =>  [Query::AGGREGATE_PARAMETER_MAX => 5]
                    ]
                ]
            ],
        ];
    }

    public function testOnProcessAutocompleteData()
    {
        $this->urlGenerator->expects($this->any())
            ->method('generate')
            ->with('oro_product_frontend_product_search', $this->isType('array'))
            ->willReturnCallback(function ($route, $parameters) {
                return sprintf(
                    '/product/search?search=%s&categoryId=%s',
                    $parameters['search'],
                    $parameters['categoryId']
                );
            });

        $this->htmlTagExtension->expects($this->any())
            ->method('htmlSanitize')
            ->willReturnCallback('strip_tags');

        $aggregatedData = [
            'category' => [
                '1|<a>first</a>|second|third' => 5,
                '2|second|third' => 3,
            ]
        ];

        $result = new Result(new Query(), [], 0, $aggregatedData);
        $event = new ProcessAutocompleteDataEvent([], 'request', $result);
        $this->listener->onProcessAutocompleteData($event);

        $this->assertEquals(
            [
                'categories' => [
                    [
                        'id' => 1,
                        'count' => 5,
                        'url' => '/product/search?search=request&categoryId=1',
                        'tree' => ['first', 'second', 'third']
                    ],
                    [
                        'id' => 2,
                        'count' => 3,
                        'url' => '/product/search?search=request&categoryId=2',
                        'tree' => ['second', 'third']
                    ]
                ]
            ],
            $event->getData()
        );
    }
}
