<?php

namespace OroB2B\Bundle\OrderBundle\Controller\Frontend;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

use Symfony\Component\HttpFoundation\Request;

use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;

use OroB2B\Bundle\OrderBundle\Controller\AbstractAjaxOrderController;
use OroB2B\Bundle\OrderBundle\Form\Type\FrontendOrderType;

class AjaxOrderController extends AbstractAjaxOrderController
{
    /**
     * @Route("/subtotals", name="orob2b_order_frontend_subtotals")
     * @Method({"POST"})
     * @AclAncestor("orob2b_order_frontend_update")
     *
     * {@inheritdoc}
     */
    public function subtotalsAction(Request $request)
    {
        return parent::subtotalsAction($request);
    }

    /**
     * {@inheritdoc}
     */
    protected function getOrderFormTypeName()
    {
        return FrontendOrderType::NAME;
    }
}
