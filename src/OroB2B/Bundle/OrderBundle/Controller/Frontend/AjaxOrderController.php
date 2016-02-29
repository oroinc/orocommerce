<?php

namespace OroB2B\Bundle\OrderBundle\Controller\Frontend;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;

use OroB2B\Bundle\OrderBundle\Controller\AjaxOrderController as BaseAjaxOrderController;
use OroB2B\Bundle\OrderBundle\Form\Type\FrontendOrderType;
use OroB2B\Bundle\OrderBundle\Entity\Order;

class AjaxOrderController extends BaseAjaxOrderController
{
    /**
     * @Route("/entry-point/{id}", name="orob2b_order_frontend_entry_point", defaults={"id" = 0})
     * @AclAncestor("orob2b_order_frontend_update")
     *
     * @param Request $request
     * @param Order|null $order
     * @return JsonResponse
     */
    public function entryPointAction(Request $request, Order $order = null)
    {
        return parent::entryPointAction($request, $order);
    }

    /**
     * @param Order $order
     * @return Form
     */
    protected function getType(Order $order)
    {
        return $this->createForm(FrontendOrderType::NAME, $order);
    }
}
