<?php

namespace Oro\Bundle\SaleBundle\Tests\Unit\EventListener;

use Oro\Bundle\ConfigBundle\Event\ConfigUpdateEvent;
use Oro\Bundle\SaleBundle\Entity\Quote;
use Oro\Bundle\SaleBundle\EventListener\ChangeProjectNameConfigurationListener;
use Oro\Bundle\SearchBundle\Engine\IndexerInterface;
use Oro\Bundle\SearchBundle\Provider\SearchMappingProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ChangeProjectNameConfigurationListenerTest extends TestCase
{
    private SearchMappingProvider&MockObject $searchMappingProvider;
    private IndexerInterface&MockObject $searchIndexer;
    private ChangeProjectNameConfigurationListener $listener;

    #[\Override]
    protected function setUp(): void
    {
        $this->searchMappingProvider = $this->createMock(SearchMappingProvider::class);
        $this->searchIndexer = $this->createMock(IndexerInterface::class);

        $this->listener = new ChangeProjectNameConfigurationListener(
            $this->searchMappingProvider,
            $this->searchIndexer
        );
    }

    public function testOnUpdateAfterWhenEnableQuoteProjectNameConfigOptionIsNotChanged(): void
    {
        $this->searchMappingProvider->expects(self::never())
            ->method('clearCache');
        $this->searchIndexer->expects(self::never())
            ->method('reindex');

        $event = new ConfigUpdateEvent(
            ['some_option' => ['old' => false, 'new' => true]],
            'app',
            0
        );
        $this->listener->onUpdateAfter($event);
    }

    public function testOnUpdateAfterWhenEnableQuoteProjectNameConfigOptionIsChanged(): void
    {
        $this->searchMappingProvider->expects(self::once())
            ->method('clearCache');
        $this->searchIndexer->expects(self::once())
            ->method('reindex')
            ->with(Quote::class);

        $event = new ConfigUpdateEvent(
            ['oro_sale.enable_quote_project_name' => ['old' => false, 'new' => true]],
            'app',
            0
        );
        $this->listener->onUpdateAfter($event);
    }
}
