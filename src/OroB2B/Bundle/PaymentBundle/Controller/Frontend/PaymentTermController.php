<?php

namespace OroB2B\Bundle\PaymentBundle\Controller\Frontend;

use Oro\Bundle\LayoutBundle\Annotation\Layout;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

class PaymentTermController extends Controller
{
    /**
     * @Route("/creditcard/test", name="orob2b_payment_methods")
     * @Layout()
     *
     * @return array
     */
    public function indexAction()
    {
        return [
            'data' => [
                'test' => 'test'
            ]
        ];
    }
}
