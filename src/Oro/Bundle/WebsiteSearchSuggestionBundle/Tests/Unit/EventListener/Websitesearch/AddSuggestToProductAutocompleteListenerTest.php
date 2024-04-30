<?php

namespace Oro\Bundle\WebsiteSearchSuggestionBundle\Tests\Unit\EventListener\Websitesearch;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ProductBundle\Event\ProcessAutocompleteDataEvent;
use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\SearchBundle\Query\Result;
use Oro\Bundle\SearchBundle\Query\SearchQueryInterface;
use Oro\Bundle\WebsiteSearchBundle\Placeholder\LocalizationIdPlaceholder;
use Oro\Bundle\WebsiteSearchSuggestionBundle\DependencyInjection\Configuration;
use Oro\Bundle\WebsiteSearchSuggestionBundle\EventListener\WebsiteSearch\AddSuggestToProductAutocompleteListener;
use Oro\Bundle\WebsiteSearchSuggestionBundle\Query\ProductSuggestionRepository;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class AddSuggestToProductAutocompleteListenerTest extends \PHPUnit\Framework\TestCase
{
    private UrlGeneratorInterface&MockObject $urlGenerator;

    private ProductSuggestionRepository&MockObject $suggestionRepository;

    private LocalizationIdPlaceholder&MockObject $localizationIdPlaceholder;

    private ConfigManager&MockObject $configManager;

    private AddSuggestToProductAutocompleteListener $listener;

    private ProcessAutocompleteDataEvent&MockObject $event;

    protected function setUp(): void
    {
        $this->listener = new AddSuggestToProductAutocompleteListener(
            $this->urlGenerator = $this->createMock(UrlGeneratorInterface::class),
            $this->suggestionRepository = $this->createMock(ProductSuggestionRepository::class),
            $this->localizationIdPlaceholder = $this->createMock(LocalizationIdPlaceholder::class),
            $this->configManager = $this->createMock(ConfigManager::class),
        );

        $this->event = $this->createMock(ProcessAutocompleteDataEvent::class);

        $this->event
            ->expects(self::once())
            ->method('getData')
            ->willReturn([
                'key_before' => 'value',
            ]);
    }

    public function testThatAutocompleteDataNotProcessedWhenMaxNumberSuggestionNegative(): void
    {
        $this->configManager
            ->expects(self::once())
            ->method('get')
            ->with(Configuration::getConfigKeyByName(
                Configuration::SEARCH_AUTOCOMPLETE_MAX_SUGGESTS
            ))
            ->willReturn(0);

        $this->event
            ->expects(self::once())
            ->method('setData')
            ->with([
                'key_before' => 'value',
                'suggests' => []
            ]);

        $this->listener->onProcessAutocompleteData($this->event);
    }

    public function testThatAutocompleteDataProcessedWhenMaxNumberSuggestionPositive(): void
    {
        $query = $this->createMock(SearchQueryInterface::class);
        $resultItem = $this->createMock(Result\Item::class);

        $maxNumber = 2;
        $defaultValue = 5;
        $data = ['phrase' => 'about suggest'];

        $this->configManager
            ->expects(self::once())
            ->method('get')
            ->with(Configuration::getConfigKeyByName(
                Configuration::SEARCH_AUTOCOMPLETE_MAX_SUGGESTS
            ))
            ->willReturn($maxNumber);

        $this->event
            ->expects(self::once())
            ->method('getQueryString')
            ->willReturn($queryString = 'query string');

        $this->localizationIdPlaceholder
            ->expects(self::once())
            ->method('getDefaultValue')
            ->willReturn($defaultValue);

        $this->suggestionRepository
            ->expects(self::once())
            ->method('getAutocompleteSuggestsSearchQuery')
            ->with($queryString, $defaultValue, $maxNumber)
            ->willReturn($query);

        $query
            ->expects(self::once())
            ->method('setHint')
            ->with('search_suggestion', $queryString);

        $query
            ->expects(self::once())
            ->method('getResult')
            ->willReturn(new Result(
                $this->createMock(Query::class),
                [$resultItem]
            ));

        $resultItem
            ->expects(self::once())
            ->method('getSelectedData')
            ->willReturn($data);

        $this->urlGenerator
            ->expects(self::once())
            ->method('generate')
            ->with('oro_product_frontend_product_search', ['search' => $data['phrase']])
            ->willReturn('url');

        $this->event
            ->expects(self::once())
            ->method('setData')
            ->with([
                'key_before' => 'value',
                'suggests' => [
                    [
                        'phrase' => 'about suggest',
                        'url' => 'url'
                    ]
                ]
            ]);

        $this->listener->onProcessAutocompleteData($this->event);
    }
}
