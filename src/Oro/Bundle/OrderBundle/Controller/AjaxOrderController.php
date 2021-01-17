<?php

namespace Oro\Bundle\OrderBundle\Controller;

use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Event\OrderEvent;
use Oro\Bundle\OrderBundle\Form\Type\OrderType;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class AjaxOrderController extends AbstractController
{
    /**
     * @Route("/entry-point/{id}", name="oro_order_entry_point", defaults={"id" = 0})
     * @AclAncestor("oro_order_update")
     *
     * @param Request $request
     * @param Order|null $order
     * @return JsonResponse
     */
    public function entryPointAction(Request $request, Order $order = null)
    {
        if (!$order) {
            $order = new Order();
            $order->setWebsite($this->get('oro_website.manager')->getDefaultWebsite());
        }

        $form = $this->getType($order);

        $submittedData = $request->get($form->getName());

        $form->submit($submittedData);

        $event = new OrderEvent($form, $form->getData(), $submittedData);
        $this->get('event_dispatcher')->dispatch($event, OrderEvent::NAME);

        return new JsonResponse($event->getData());
    }

    /**
     * @param Order $order
     * @return Form
     */
    protected function getType(Order $order)
    {
        return $this->createForm(OrderType::class, $order);
    }
}
