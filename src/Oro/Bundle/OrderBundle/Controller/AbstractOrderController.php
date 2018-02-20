<?php

namespace Oro\Bundle\OrderBundle\Controller;

use Oro\Bundle\OrderBundle\Provider\OrderAddressSecurityProvider;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

abstract class AbstractOrderController extends Controller
{
    /**
     * @return OrderAddressSecurityProvider
     */
    protected function getOrderAddressSecurityProvider()
    {
        return $this->get('oro_order.order.provider.order_address_security');
    }
}
