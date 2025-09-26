<?php

declare(strict_types=1);

namespace Oro\Bundle\OrderBundle\Controller;

use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\SecurityBundle\Attribute\AclAncestor;
use Symfony\Component\HttpFoundation\Response;

/**
 * Creates an order PDF document if it does not exist and redirects to its download URL.
 *
 * Use {@link OrderPdfDocumentUrlGenerator} if you need to get PDF document URL for already existing document.
 */
final class OrderPdfDocumentDownloadController extends AbstractOrderPdfDocumentDownloadController
{
    #[AclAncestor(id: 'oro_order_view')]
    public function __invoke(Order $order): Response
    {
        return parent::__invoke($order);
    }
}
