<?php

namespace Oro\Bundle\CheckoutBundle\Controller\Frontend;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\LayoutBundle\Annotation\Layout;
use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Frontend controller for open orders page.
 */
class OpenOrdersController extends AbstractController
{
    /**
     * @Route("/", name="oro_checkout_frontend_open_orders")
     * @Layout()
     * @Acl(
     *      id="oro_checkout_frontend_view",
     *      type="entity",
     *      class="OroCheckoutBundle:Checkout",
     *      permission="VIEW",
     *      group_name="commerce"
     * )
     *
     * @return array
     */
    public function openOrdersAction()
    {
        if (!$this->get(ConfigManager::class)->get('oro_checkout.frontend_show_open_orders')) {
            throw new NotFoundHttpException();
        }

        return [];
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedServices()
    {
        return array_merge(
            parent::getSubscribedServices(),
            [
                ConfigManager::class,
            ]
        );
    }
}
