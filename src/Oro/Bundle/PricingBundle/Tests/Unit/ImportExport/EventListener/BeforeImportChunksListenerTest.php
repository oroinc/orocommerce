<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\ImportExport\EventListener;

use Doctrine\ORM\EntityRepository;
use Oro\Bundle\ImportExportBundle\Event\BeforeImportChunksEvent;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\ProductPrice;
use Oro\Bundle\PricingBundle\Entity\Repository\ProductPriceRepository;
use Oro\Bundle\PricingBundle\ImportExport\EventListener\BeforeImportChunksListener;
use Oro\Bundle\PricingBundle\Sharding\ShardManager;
use Oro\Component\Testing\Unit\EntityTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Bridge\Doctrine\ManagerRegistry;

class BeforeImportChunksListenerTest extends TestCase
{
    use EntityTrait;

    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $registry;

    /** @var ShardManager|\PHPUnit\Framework\MockObject\MockObject */
    private $shardManager;

    /** @var EntityRepository|\PHPUnit\Framework\MockObject\MockObject */
    private $priceListRepository;

    /** @var ProductPriceRepository|\PHPUnit\Framework\MockObject\MockObject */
    private $productPriceRepository;

    /** @var BeforeImportChunksListener */
    private $listener;

    protected function setUp(): void
    {
        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->shardManager = $this->createMock(ShardManager::class);
        $this->priceListRepository = $this->createMock(EntityRepository::class);
        $this->productPriceRepository = $this->createMock(ProductPriceRepository::class);

        $this->listener = new BeforeImportChunksListener($this->registry, $this->shardManager);
    }

    /**
     * @dataProvider onBeforeImportChunksWithoutPriceListDataProvider
     */
    public function testOnBeforeImportChunksWithoutPriceList(array $body)
    {
        $event = new BeforeImportChunksEvent($body);

        $this->registry->expects($this->never())
            ->method('getRepository')
            ->with(PriceList::class);
        $this->registry->expects($this->never())
            ->method('getRepository')
            ->with(ProductPrice::class);

        $this->listener->onBeforeImportChunks($event);
    }

    /**
     * @return array
     */
    public function onBeforeImportChunksWithoutPriceListDataProvider()
    {
        return [
            'empty processor alias' => [
                'body' => [],
            ],
            'unsupported processor alias' => [
                'body' => [
                    'processorAlias' => 'unsupportedAlias',
                ],
            ],
            'no price list id' => [
                'body' => [
                    'processorAlias' => BeforeImportChunksListener::RESET_PROCESSOR_ALIAS,
                ],
            ],
        ];
    }

    public function testOnBeforeImportChunksNonExistentPriceList()
    {
        $body['processorAlias'] = BeforeImportChunksListener::RESET_PROCESSOR_ALIAS;
        $body['options']['price_list_id'] = 15;
        $event = new BeforeImportChunksEvent($body);

        $this->registry->expects($this->once())
            ->method('getRepository')
            ->with(PriceList::class)
            ->willReturn($this->priceListRepository);

        $this->priceListRepository->expects($this->once())
            ->method('find')
            ->with(15)
            ->willReturn(null);

        $this->listener->onBeforeImportChunks($event);
    }

    public function testOnBeforeImportChunks()
    {
        $body['processorAlias'] = BeforeImportChunksListener::RESET_PROCESSOR_ALIAS;
        $body['options']['price_list_id'] = 16;
        $event = new BeforeImportChunksEvent($body);

        $priceList = $this->getEntity(PriceList::class, ['id' => 16]);

        $this->registry->expects($this->exactly(2))
            ->method('getRepository')
            ->withConsecutive([PriceList::class], [ProductPrice::class])
            ->willReturnOnConsecutiveCalls($this->priceListRepository, $this->productPriceRepository);

        $this->priceListRepository->expects($this->once())
            ->method('find')
            ->with(16)
            ->willReturn($priceList);

        $this->productPriceRepository->expects($this->once())
            ->method('deleteByPriceList')
            ->with($this->shardManager, $priceList);

        $this->listener->onBeforeImportChunks($event);
    }
}
