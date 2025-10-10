<?php

declare(strict_types=1);

namespace Oro\Bundle\OrderBundle\Tests\Unit\EventListener\PdfDocument;

use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\OrderBundle\DependencyInjection\Configuration;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\EventListener\PdfDocument\CreatePdfDocumentOnCheckoutFinishListener;
use Oro\Bundle\OrderBundle\PdfDocument\Demand\GenericOrderPdfDocumentDemand;
use Oro\Bundle\OrderBundle\PdfDocument\Manager\OrderPdfDocumentManagerInterface;
use Oro\Bundle\OrderBundle\PdfDocument\OrderPdfDocumentType;
use Oro\Bundle\PdfGeneratorBundle\Entity\PdfDocument;
use Oro\Bundle\PdfGeneratorBundle\PdfOptionsPreset\PdfOptionsPreset;
use Oro\Component\Action\Event\ExtendableActionEvent;
use Oro\Component\Action\Event\ExtendableEventData;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class CreatePdfDocumentOnCheckoutFinishListenerTest extends TestCase
{
    private OrderPdfDocumentManagerInterface&MockObject $orderPdfDocumentManager;
    private ManagerRegistry&MockObject $doctrine;
    private ConfigManager&MockObject $configManager;
    private EntityManager&MockObject $entityManager;
    private CreatePdfDocumentOnCheckoutFinishListener $listener;
    private string $pdfDocumentType = OrderPdfDocumentType::DEFAULT;
    private string $pdfOptionsPreset = PdfOptionsPreset::DEFAULT;

    protected function setUp(): void
    {
        $this->orderPdfDocumentManager = $this->createMock(OrderPdfDocumentManagerInterface::class);
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->entityManager = $this->createMock(EntityManager::class);

        $this->listener = new CreatePdfDocumentOnCheckoutFinishListener(
            $this->orderPdfDocumentManager,
            $this->doctrine,
            $this->configManager,
            $this->pdfDocumentType,
            $this->pdfOptionsPreset
        );
    }

    public function testOnCheckoutFinishCreatesPdfDocumentSuccessfully(): void
    {
        $checkout = new Checkout();
        $order = new Order();
        $pdfDocument = new PdfDocument();

        $data = new ExtendableEventData([
            'checkout' => $checkout,
            'order' => $order,
        ]);

        $event = new ExtendableActionEvent($data);

        $this->configManager
            ->expects(self::once())
            ->method('get')
            ->with(Configuration::getConfigKey(Configuration::GENERATE_ORDER_PDF_ON_CHECKOUT_FINISH))
            ->willReturn(true);

        $this->orderPdfDocumentManager
            ->expects(self::once())
            ->method('hasPdfDocument')
            ->with($order, $this->pdfDocumentType)
            ->willReturn(false);

        $this->orderPdfDocumentManager
            ->expects(self::once())
            ->method('createPdfDocument')
            ->with(
                self::callback(function (GenericOrderPdfDocumentDemand $demand) use ($order) {
                    return $demand->getSourceEntity() === $order
                        && $demand->getPdfDocumentType() === $this->pdfDocumentType
                        && $demand->getPdfOptionsPreset() === $this->pdfOptionsPreset;
                })
            )
            ->willReturn($pdfDocument);

        $this->doctrine
            ->expects(self::once())
            ->method('getManagerForClass')
            ->with(PdfDocument::class)
            ->willReturn($this->entityManager);

        $this->entityManager
            ->expects(self::once())
            ->method('persist')
            ->with($pdfDocument);

        $this->entityManager
            ->expects(self::once())
            ->method('flush')
            ->with($pdfDocument);

        $this->listener->onCheckoutFinish($event);
    }

    public function testOnCheckoutFinishDoesNothingWhenCheckoutIsNotProvided(): void
    {
        $data = new ExtendableEventData([]);

        $event = new ExtendableActionEvent($data);

        $this->configManager
            ->expects(self::never())
            ->method('get');
        $this->orderPdfDocumentManager
            ->expects(self::never())
            ->method('hasPdfDocument');
        $this->orderPdfDocumentManager
            ->expects(self::never())
            ->method('createPdfDocument');

        $this->listener->onCheckoutFinish($event);
    }

    public function testOnCheckoutFinishDoesNothingWhenOrderIsNotProvided(): void
    {
        $checkout = new Checkout();

        $data = new ExtendableEventData([
            'checkout' => $checkout,
        ]);

        $event = new ExtendableActionEvent($data);

        $this->configManager
            ->expects(self::never())
            ->method('get');
        $this->orderPdfDocumentManager
            ->expects(self::never())
            ->method('hasPdfDocument');
        $this->orderPdfDocumentManager
            ->expects(self::never())
            ->method('createPdfDocument');

        $this->listener->onCheckoutFinish($event);
    }

    public function testOnCheckoutFinishDoesNothingWhenConfigurationIsDisabled(): void
    {
        $checkout = new Checkout();
        $order = new Order();

        $data = new ExtendableEventData([
            'checkout' => $checkout,
            'order' => $order,
        ]);

        $event = new ExtendableActionEvent($data);

        $this->configManager
            ->expects(self::once())
            ->method('get')
            ->with(Configuration::getConfigKey(Configuration::GENERATE_ORDER_PDF_ON_CHECKOUT_FINISH))
            ->willReturn(false);

        $this->orderPdfDocumentManager
            ->expects(self::never())
            ->method('hasPdfDocument');
        $this->orderPdfDocumentManager
            ->expects(self::never())
            ->method('createPdfDocument');

        $this->listener->onCheckoutFinish($event);
    }

    public function testOnCheckoutFinishDoesNothingWhenPdfDocumentAlreadyExists(): void
    {
        $checkout = new Checkout();
        $order = new Order();

        $data = new ExtendableEventData([
            'checkout' => $checkout,
            'order' => $order,
        ]);

        $event = new ExtendableActionEvent($data);

        $this->configManager
            ->expects(self::once())
            ->method('get')
            ->with(Configuration::getConfigKey(Configuration::GENERATE_ORDER_PDF_ON_CHECKOUT_FINISH))
            ->willReturn(true);

        $this->orderPdfDocumentManager
            ->expects(self::once())
            ->method('hasPdfDocument')
            ->with($order, $this->pdfDocumentType)
            ->willReturn(true);

        $this->orderPdfDocumentManager
            ->expects(self::never())
            ->method('createPdfDocument');
        $this->doctrine
            ->expects(self::never())
            ->method('getManagerForClass');

        $this->listener->onCheckoutFinish($event);
    }
}
