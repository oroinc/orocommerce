<?php

declare(strict_types=1);

namespace Oro\Bundle\OrderBundle\Controller\Frontend;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\OrderBundle\Controller\AbstractOrderPdfDocumentDownloadController;
use Oro\Bundle\OrderBundle\DependencyInjection\Configuration;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\SecurityBundle\Attribute\AclAncestor;
use Symfony\Component\HttpFoundation\Response;

/**
 * Creates an order PDF document if it does not exist and redirects to its download URL on storefront.
 *
 * Use {@link OrderPdfDocumentUrlGenerator} if you need to get PDF document URL for already existing document.
 */
final class FrontendOrderPdfDocumentDownloadController extends AbstractOrderPdfDocumentDownloadController
{
    #[\Override]
    public static function getSubscribedServices(): array
    {
        return [
            ...parent::getSubscribedServices(),
            ConfigManager::class,
        ];
    }

    #[AclAncestor(id: 'oro_order_frontend_view')]
    public function __invoke(Order $order): Response
    {
        /** @var ConfigManager $configManager */
        $configManager = $this->container->get(ConfigManager::class);
        if (!$configManager->get(
            Configuration::getConfigKey(Configuration::ENABLE_ORDER_PDF_DOWNLOAD_IN_STOREFRONT)
        )) {
            throw $this->createNotFoundException();
        }

        return parent::__invoke($order);
    }
}
