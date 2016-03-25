<?php

namespace OroB2B\Bundle\OrderBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

use OroB2B\Bundle\OrderBundle\Entity\Order;
use OroB2B\Bundle\PricingBundle\SubtotalProcessor\Model\Subtotal;

abstract class AbstractAjaxOrderController extends Controller
{
    /**
     * @return string
     */
    abstract protected function getOrderFormTypeName();

    /**
     * @return \OroB2B\Bundle\PricingBundle\SubtotalProcessor\TotalProcessorProvider
     */
    protected function getTotalProcessor()
    {
        return $this->get('orob2b_pricing.subtotal_processor.total_processor_provider');
    }
}
