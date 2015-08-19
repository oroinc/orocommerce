<?php

namespace OroB2B\Bundle\OrderBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

use OroB2B\Bundle\OrderBundle\Entity\Order;
use OroB2B\Bundle\OrderBundle\Form\Type\OrderType;
use OroB2B\Bundle\OrderBundle\Model\Subtotal;

abstract class AbstractAjaxOrderController extends Controller
{
    /**
     * Get order subtotals
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function subtotalsAction(Request $request)
    {
        $orderClass = $this->getParameter('orob2b_order.entity.order.class');
        $id = $request->get('id');
        if ($id) {
            /** @var Order $order */
            $order = $this->getDoctrine()->getManagerForClass($orderClass)->find($orderClass, $id);
        } else {
            $order = new $orderClass();
        }

        $form = $this->createForm(OrderType::NAME, $order);
        $form->submit($this->get('request'));

        $subtotals = $this->get('orob2b_order.provider.subtotals')->getSubtotals($order);
        $subtotals = $subtotals->map(
            function (Subtotal $subtotal) {
                return $subtotal->toArray();
            }
        )->toArray();

        return new JsonResponse(['subtotals' => $subtotals]);
    }
}
