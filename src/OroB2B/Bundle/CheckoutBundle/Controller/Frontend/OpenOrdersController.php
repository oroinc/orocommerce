<?php

namespace OroB2B\Bundle\CheckoutBundle\Controller\Frontend;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Oro\Bundle\LayoutBundle\Annotation\Layout;
use Oro\Bundle\SecurityBundle\Annotation\Acl;

class OpenOrdersController extends Controller
{
    /**
     * @Route("/", name="orob2b_checkout_frontend_open_orders")
     * @Layout(vars={"entity_class"})
     * @Acl(
     *      id="orob2b_order_frontend_view",
     *      type="entity",
     *      class="OroB2BOrderBundle:Order",
     *      permission="ACCOUNT_VIEW",
     *      group_name="commerce"
     * )
     *
     * @return array
     */
    public function openOrdersAction()
    {
        return [
            'entity_class' => $this->container->getParameter('orob2b_order.entity.order.class'),
        ];
    }
}

