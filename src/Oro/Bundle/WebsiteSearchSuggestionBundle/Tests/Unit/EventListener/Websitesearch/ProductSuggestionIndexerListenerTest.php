<?php

namespace Oro\Bundle\WebsiteSearchSuggestionBundle\Tests\Unit\EventListener\Websitesearch;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteSearchBundle\Event\IndexEntityEvent;
use Oro\Bundle\WebsiteSearchBundle\Manager\WebsiteContextManager;
use Oro\Bundle\WebsiteSearchSuggestionBundle\Entity\Suggestion;
use Oro\Bundle\WebsiteSearchSuggestionBundle\EventListener\WebsiteSearch\ProductSuggestionIndexerListener;
use PHPUnit\Framework\MockObject\MockObject;

final class ProductSuggestionIndexerListenerTest extends \PHPUnit\Framework\TestCase
{
    private ProductSuggestionIndexerListener $listener;

    private WebsiteContextManager&MockObject $websiteContextManager;

    private ManagerRegistry&MockObject $doctrine;

    private IndexEntityEvent $event;

    protected function setUp(): void
    {
        $this->listener = new ProductSuggestionIndexerListener(
            $this->websiteContextManager = $this->createMock(WebsiteContextManager::class),
            $this->doctrine = $this->createMock(ManagerRegistry::class),
        );

        $this->event = $this->createMock(IndexEntityEvent::class);
    }

    public function testThatEventNotProcessedWithoutWebsite(): void
    {
        $this->event
            ->expects(self::once())
            ->method('getContext')
            ->willReturn([]);

        $this->websiteContextManager
            ->expects(self::once())
            ->method('getWebsiteId');

        $this->event
            ->expects(self::once())
            ->method('stopPropagation');

        $this->listener->onWebsiteSearchIndex($this->event);
    }

    public function testThatProductSuggestionsProcessed(): void
    {
        $manager = $this->createMock(ObjectManager::class);
        $suggestion = $this->createMock(Suggestion::class);
        $localisation = $this->createMock(Localization::class);
        $website = $this->createMock(Website::class);

        $websiteId = 1;
        $suggestionId = 1;
        $localisationId = 2;
        $wordsCount = 5;
        $suggest = 'suggesttext';

        $this->event
            ->expects(self::once())
            ->method('getContext')
            ->willReturn([]);

        $this->websiteContextManager
            ->expects(self::once())
            ->method('getWebsiteId')
            ->willReturn($websiteId);

        $this->doctrine
            ->expects(self::once())
            ->method('getManagerForClass')
            ->with(Website::class)
            ->willReturn($manager);

        $manager
            ->expects(self::once())
            ->method('find')
            ->with(Website::class, $websiteId)
            ->willReturn($website);

        $this->event
            ->expects(self::once())
            ->method('getEntities')
            ->willReturn([$suggestion]);

        $suggestion
            ->expects($this->exactly(3))
            ->method('getId')
            ->willReturn($suggestionId);

        $suggestion
            ->expects(self::once())
            ->method('getLocalization')
            ->willReturn($localisation);

        $suggestion
            ->expects(self::once())
            ->method('getWordsCount')
            ->willReturn($wordsCount);

        $suggestion
            ->expects(self::once())
            ->method('getPhrase')
            ->willReturn($suggest);

        $localisation
            ->expects(self::once())
            ->method('getId')
            ->willReturn($localisationId);

        $this->event
            ->expects(self::exactly(3))
            ->method('addField')
            ->withConsecutive(
                [$suggestionId, 'localization_id', $localisationId],
                [$suggestionId, 'words_count', $wordsCount],
                [$suggestionId, 'phrase', $suggest],
            );

        $this->listener->onWebsiteSearchIndex($this->event);
    }
}
