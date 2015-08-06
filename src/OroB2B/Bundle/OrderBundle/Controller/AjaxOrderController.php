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
     * @Route("/subtotals", name="orob2b_order_subtotals")
     * @Method({"POST"})
     * @Acl(
     *      id="orob2b_order_update",
     *      type="entity",
     *      class="OroB2BOrderBundle:Order",
     *      permission="EDIT"
     * )
     *
     * @return JsonResponse
     */
    public function subtotalsAction()
    {
        $orderClass = $this->getParameter('orob2b_order.entity.order.class');
        $id = $this->get('request')->get('id');
        if ($id) {
            /** @var Order $order */
            $order = $this->getDoctrine()->getManagerForClass($orderClass)->find($orderClass, $id);
        } else {
            $order = new $orderClass();
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
