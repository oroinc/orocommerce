<?php

namespace Oro\Bundle\WebsiteSearchSuggestionBundle\Tests\Unit\EventListener\Doctrine;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\UnitOfWork;
use Doctrine\Persistence\Event\ManagerEventArgs;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductName;
use Oro\Bundle\WebsiteSearchSuggestionBundle\Async\Topic\Generation\GenerateSuggestionsTopic;
use Oro\Bundle\WebsiteSearchSuggestionBundle\EventListener\Doctrine\CreateProductSuggestionListener;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\Testing\Unit\EntityTrait;
use PHPUnit\Framework\MockObject\MockObject;

final class CreateProductSuggestionListenerTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    private MessageProducerInterface&MockObject $producer;

    private CreateProductSuggestionListener $createProductSuggestionListener;

    private ProductName $productName;

    private Product $product;

    private UnitOfWork&MockObject $unitOfWork;

    private ManagerEventArgs $args;

    protected function setUp(): void
    {
        $this->producer = $this->createMock(MessageProducerInterface::class);
        $this->productName = $this->createMock(ProductName::class);
        $this->unitOfWork = $this->createMock(UnitOfWork::class);
        $this->product = $this->getEntity(Product::class, ['id' => 1]);
        $objectManager = $this->createMock(EntityManager::class);

        $objectManager
            ->method('getUnitOfWork')
            ->willReturn($this->unitOfWork);

        $this->productName = $this->getEntity(ProductName::class, ['product' => $this->product]);

        $this->args = new ManagerEventArgs($objectManager);

        $this->createProductSuggestionListener = new CreateProductSuggestionListener(
            $this->producer,
            ['sku', 'status', 'inventory_status']
        );
    }

    public function testThatProductEntitySkuProcessedOnPostFlush(): void
    {
        $this->product = $this->getEntity(Product::class, [
            'sku' => 'sku',
            'id' => 1,
        ]);

        $this->unitOfWork
            ->expects(self::once())
            ->method('getScheduledEntityInsertions')
            ->willReturn([$this->product]);

        $this->unitOfWork
            ->expects(self::once())
            ->method('getScheduledEntityUpdates')
            ->willReturn([]);

        $this->producer
            ->expects(self::once())
            ->method('send')
            ->with(
                GenerateSuggestionsTopic::getName(),
                [GenerateSuggestionsTopic::PRODUCT_IDS => [1]]
            );

        $this->createProductSuggestionListener->onFlush($this->args);
        $this->createProductSuggestionListener->postFlush($this->args);
    }

    public function testThatProductNameEntityProcessedOnPostPersist(): void
    {
        $this->unitOfWork
            ->expects(self::once())
            ->method('getScheduledEntityInsertions')
            ->willReturn([$this->productName]);

        $this->unitOfWork
            ->expects(self::once())
            ->method('getScheduledEntityUpdates')
            ->willReturn([]);

        $this->producer
            ->expects(self::once())
            ->method('send')
            ->with(
                GenerateSuggestionsTopic::getName(),
                [GenerateSuggestionsTopic::PRODUCT_IDS => [1]]
            );

        $this->createProductSuggestionListener->onFlush($this->args);
        $this->createProductSuggestionListener->postFlush($this->args);
    }

    /**
     * @dataProvider validKeysProvider
     */
    public function testThatProductProcessedOnPostUpdate(string $validKey, string $value): void
    {
        $this->unitOfWork
            ->expects(self::once())
            ->method('getScheduledEntityInsertions')
            ->willReturn([]);

        $this->unitOfWork
            ->expects(self::once())
            ->method('getScheduledEntityUpdates')
            ->willReturn([$this->product]);

        $this->unitOfWork
            ->expects(self::once())
            ->method('getEntityChangeSet')
            ->with($this->product)
            ->willReturn([
                'not valid key' => 'not valid value',
                $validKey => $value,
            ]);

        $this->producer
            ->expects(self::once())
            ->method('send')
            ->with(
                GenerateSuggestionsTopic::getName(),
                [GenerateSuggestionsTopic::PRODUCT_IDS => [1]]
            );

        $this->createProductSuggestionListener->onFlush($this->args);
        $this->createProductSuggestionListener->postFlush($this->args);
    }

    public function testThatProductNameProcessedOnPostUpdate(): void
    {
        $this->unitOfWork
            ->expects(self::once())
            ->method('getScheduledEntityInsertions')
            ->willReturn([]);

        $this->unitOfWork
            ->expects(self::once())
            ->method('getScheduledEntityUpdates')
            ->willReturn([$this->productName]);

        $this->producer
            ->expects(self::once())
            ->method('send')
            ->with(
                GenerateSuggestionsTopic::getName(),
                [GenerateSuggestionsTopic::PRODUCT_IDS => [1]]
            );

        $this->createProductSuggestionListener->onFlush($this->args);
        $this->createProductSuggestionListener->postFlush($this->args);
    }

    public function testThatPostFlushWillNotSendMessage(): void
    {
        $this->unitOfWork
            ->expects(self::once())
            ->method('getScheduledEntityInsertions')
            ->willReturn([]);

        $this->unitOfWork
            ->expects(self::once())
            ->method('getScheduledEntityUpdates')
            ->willReturn([new \stdClass()]);

        $this->createProductSuggestionListener->onFlush($this->args);
        $this->createProductSuggestionListener->postFlush($this->args);

        $this->producer
            ->expects($this->never())
            ->method('send');
    }

    private function validKeysProvider(): array
    {
        return [
            'sku' => ['sku', 'sku value'],
            'status' => ['status', 'status value'],
            'inventory_status' => ['inventory_status', 'inventory_status value'],
        ];
    }
}
