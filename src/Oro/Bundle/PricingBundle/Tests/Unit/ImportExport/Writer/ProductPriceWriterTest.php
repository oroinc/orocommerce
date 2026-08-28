<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\ImportExport\Writer;

use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\BatchBundle\Entity\JobExecution;
use Oro\Bundle\BatchBundle\Entity\JobInstance;
use Oro\Bundle\BatchBundle\Entity\StepExecution;
use Oro\Bundle\ImportExportBundle\Context\ContextInterface;
use Oro\Bundle\ImportExportBundle\Context\ContextRegistry;
use Oro\Bundle\PlatformBundle\Manager\OptionalListenerManager;
use Oro\Bundle\PricingBundle\Entity\ProductPrice;
use Oro\Bundle\PricingBundle\ImportExport\Writer\ProductPriceWriter;
use Oro\Bundle\PricingBundle\Manager\PriceManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Checks that the optional listeners disabled while product prices are written by import
 * are restored, including when the write fails.
 */
class ProductPriceWriterTest extends TestCase
{
    private const LISTENERS = [
        'oro_pricing.entity_listener.product_price_cpl',
        'oro_pricing.entity_listener.price_list_to_product',
    ];

    private EntityManager&MockObject $entityManager;
    private PriceManager&MockObject $priceManager;
    private OptionalListenerManager&MockObject $listenerManager;

    private ProductPriceWriter $writer;

    #[\Override]
    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManager::class);
        $this->priceManager = $this->createMock(PriceManager::class);
        $this->listenerManager = $this->createMock(OptionalListenerManager::class);

        $registry = $this->createMock(ManagerRegistry::class);
        $registry->expects(self::any())
            ->method('getManager')
            ->willReturn($this->entityManager);

        $contextRegistry = $this->createMock(ContextRegistry::class);
        $contextRegistry->expects(self::any())
            ->method('getByStepExecution')
            ->willReturn($this->createMock(ContextInterface::class));

        $this->writer = new ProductPriceWriter(
            $registry,
            $this->createMock(EventDispatcherInterface::class),
            $contextRegistry,
            $this->createMock(LoggerInterface::class),
            $this->priceManager,
            $this->listenerManager
        );

        foreach (self::LISTENERS as $listener) {
            $this->writer->disableListener($listener);
        }

        $jobExecution = new JobExecution();
        $jobExecution->setJobInstance(new JobInstance());
        $this->writer->setStepExecution(new StepExecution('step', $jobExecution));
    }

    public function testListenersAreEnabledAfterWrite(): void
    {
        $price = new ProductPrice();

        $this->listenerManager->expects(self::once())
            ->method('disableListeners')
            ->with(self::LISTENERS);
        $this->listenerManager->expects(self::once())
            ->method('enableListeners')
            ->with(self::LISTENERS);

        $this->priceManager->expects(self::once())
            ->method('persist')
            ->with($price);
        $this->priceManager->expects(self::once())
            ->method('flush');
        $this->entityManager->expects(self::once())
            ->method('flush');

        $this->writer->write([$price]);
    }

    public function testListenersAreEnabledWhenSaveFails(): void
    {
        $exception = new \RuntimeException('Flush failed');

        $this->listenerManager->expects(self::once())
            ->method('disableListeners')
            ->with(self::LISTENERS);
        // Listeners must be restored, otherwise they stay disabled for the rest of the process.
        $this->listenerManager->expects(self::once())
            ->method('enableListeners')
            ->with(self::LISTENERS);

        $this->priceManager->expects(self::once())
            ->method('flush')
            ->willThrowException($exception);
        $this->entityManager->expects(self::never())
            ->method('flush');
        $this->entityManager->expects(self::once())
            ->method('rollback');

        $this->expectExceptionObject($exception);

        $this->writer->write([new ProductPrice()]);
    }
}
