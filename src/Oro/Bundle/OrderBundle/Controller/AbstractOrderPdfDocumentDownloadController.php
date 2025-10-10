<?php

declare(strict_types=1);

namespace Oro\Bundle\OrderBundle\Controller;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\PdfDocument\Demand\GenericOrderPdfDocumentDemand;
use Oro\Bundle\OrderBundle\PdfDocument\Manager\OrderPdfDocumentManagerInterface;
use Oro\Bundle\OrderBundle\PdfDocument\UrlGenerator\OrderPdfDocumentUrlGeneratorInterface;
use Oro\Bundle\PdfGeneratorBundle\Entity\PdfDocument;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

/**
 * Creates an order PDF document if it does not exist and redirects to its download URL.
 *
 * Use {@link OrderPdfDocumentUrlGenerator} if you need to get PDF document URL for already existing document.
 */
abstract class AbstractOrderPdfDocumentDownloadController extends AbstractController
{
    #[\Override]
    public static function getSubscribedServices(): array
    {
        return [
            ...parent::getSubscribedServices(),
            ManagerRegistry::class,
            OrderPdfDocumentManagerInterface::class,
            OrderPdfDocumentUrlGeneratorInterface::class,
        ];
    }

    public function __invoke(Order $order): Response
    {
        /** @var OrderPdfDocumentManagerInterface $orderPdfDocumentManager */
        $orderPdfDocumentManager = $this->container->get(OrderPdfDocumentManagerInterface::class);
        $pdfDocumentType = $this->getParameter('oro_order.pdf_document.order_default.pdf_document_type');
        if (!$orderPdfDocumentManager->hasPdfDocument($order, $pdfDocumentType)) {
            $pdfOptionsPreset = $this->getParameter('oro_order.pdf_document.order_default.pdf_options_preset');
            $pdfDocumentDemand = new GenericOrderPdfDocumentDemand(
                sourceEntity: $order,
                pdfDocumentType: $pdfDocumentType,
                pdfOptionsPreset: $pdfOptionsPreset
            );
            $pdfDocument = $orderPdfDocumentManager->createPdfDocument($pdfDocumentDemand);

            /** @var ManagerRegistry $doctrine */
            $doctrine = $this->container->get(ManagerRegistry::class);
            $doctrine->getManagerForClass(PdfDocument::class)->flush($pdfDocument);
        } else {
            $pdfDocument = $orderPdfDocumentManager->updatePdfDocument($order, $pdfDocumentType);
            $doctrine = $this->container->get(ManagerRegistry::class);
            $doctrine->getManagerForClass(PdfDocument::class)->flush($pdfDocument);
        }

        /** @var OrderPdfDocumentUrlGeneratorInterface $orderPdfDocumentUrlGenerator */
        $orderPdfDocumentUrlGenerator = $this->container->get(OrderPdfDocumentUrlGeneratorInterface::class);

        return $this->redirect($orderPdfDocumentUrlGenerator->generateUrl($order, $pdfDocumentType));
    }
}
