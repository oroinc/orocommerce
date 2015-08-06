<?php

namespace OroB2B\Bundle\OrderBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

use Oro\Bundle\SecurityBundle\Annotation\Acl;

use OroB2B\Bundle\OrderBundle\Entity\Order;
use OroB2B\Bundle\OrderBundle\Form\Type\OrderType;
use OroB2B\Bundle\OrderBundle\Model\Subtotal;

class AjaxOrderController extends Controller
{
    /**
     * Get order subtotals
     *
     * @Route("/subtotals/{id}", name="orob2b_order_subtotals", requirements={"id"="\d*"})
     * @Method({"POST"})
     * @Acl(
     *      id="orob2b_order_update",
     *      type="entity",
     *      class="OroB2BOrderBundle:Order",
     *      permission="EDIT"
     * )
     *
     * @param Order $order
     *
     * @return JsonResponse
     */
    public function subtotalsAction(Order $order = null)
    {
        if (!$order) {
            $order = new Order();
        }

        $form = $this->createForm(OrderType::NAME, $order);
        $form->submit($this->get('request'));

        if ($form->isValid()) {
            $subtotals = $this->get('orob2b_order.provider.subtotals')->getSubtotals($order);
            $subtotals = $subtotals->map(
                function (Subtotal $subtotal) {
                    return $subtotal->toArray();
                }
            )->toArray();
        } else {
            $subtotals = false;
        }

        return new JsonResponse(['subtotals' => $subtotals]);
    }
}
