<?php

namespace Oro\Bundle\CheckoutBundle\Controller\Frontend;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\LayoutBundle\Attribute\Layout;
use Oro\Bundle\SecurityBundle\Attribute\Acl;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Frontend controller for open orders page.
 */
class OpenOrdersController extends AbstractController
{
    /**
     * @return array
     */
    #[Route(path: '/', name: 'oro_checkout_frontend_open_orders')]
    #[Layout]
    #[Acl(
        id: 'oro_checkout_frontend_view',
        type: 'entity',
        class: Checkout::class,
        permission: 'VIEW',
        groupName: 'commerce'
    )]
    public function openOrdersAction()
    {
        if (!$this->container->get(ConfigManager::class)->get('oro_checkout.frontend_show_open_orders')) {
            throw new NotFoundHttpException();
        }

        return [];
    }

    #[\Override]
    public static function getSubscribedServices(): array
    {
        return array_merge(
            parent::getSubscribedServices(),
            [
                ConfigManager::class,
            ]
        );
    }
}
