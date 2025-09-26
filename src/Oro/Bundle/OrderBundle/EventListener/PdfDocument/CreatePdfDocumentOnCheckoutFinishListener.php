<?php

declare(strict_types=1);

namespace Oro\Bundle\OrderBundle\EventListener\PdfDocument;

use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\OrderBundle\DependencyInjection\Configuration;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\PdfDocument\Demand\GenericOrderPdfDocumentDemand;
use Oro\Bundle\OrderBundle\PdfDocument\Manager\OrderPdfDocumentManagerInterface;
use Oro\Bundle\PdfGeneratorBundle\Entity\PdfDocument;
use Oro\Component\Action\Event\ExtendableActionEvent;

/**
 * Creates a PDF document for an order when the checkout is finished.
 */
final class CreatePdfDocumentOnCheckoutFinishListener
{
    public function __construct(
        private readonly OrderPdfDocumentManagerInterface $orderPdfDocumentManager,
        private readonly ManagerRegistry $doctrine,
        private readonly ConfigManager $configManager,
        private readonly string $pdfDocumentType,
        private readonly string $pdfOptionsPreset
    ) {
    }

    public function onCheckoutFinish(ExtendableActionEvent $event): void
    {
        $checkout = $event->getData()->get('checkout');
        if (!$checkout instanceof Checkout) {
            return;
        }

        $order = $event->getData()->get('order');
        if (!$order instanceof Order) {
            return;
        }

        if (!$this->configManager->get(
            Configuration::getConfigKey(Configuration::GENERATE_ORDER_PDF_ON_CHECKOUT_FINISH)
        )) {
            return;
        }

        if ($this->orderPdfDocumentManager->hasPdfDocument($order, $this->pdfDocumentType)) {
            return;
        }

        $orderPdfDocumentDemand = new GenericOrderPdfDocumentDemand(
            sourceEntity: $order,
            pdfDocumentType: $this->pdfDocumentType,
            pdfOptionsPreset: $this->pdfOptionsPreset
        );
        $pdfDocument = $this->orderPdfDocumentManager->createPdfDocument($orderPdfDocumentDemand);

        /** @var EntityManager $entityManager */
        $entityManager = $this->doctrine->getManagerForClass(PdfDocument::class);
        $entityManager->persist($pdfDocument);
        $entityManager->flush($pdfDocument);
    }
}
