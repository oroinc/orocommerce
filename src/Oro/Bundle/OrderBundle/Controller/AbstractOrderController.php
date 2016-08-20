<?php

namespace Oro\Bundle\OrderBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Oro\Bundle\OrderBundle\Provider\OrderAddressSecurityProvider;

abstract class AbstractOrderController extends Controller
{
    /**
     * @return OrderAddressSecurityProvider
     */
    protected function getOrderAddressSecurityProvider()
    {
        return $this->get('orob2b_order.order.provider.order_address_security');
    }
}
