<?php

namespace Oro\Bundle\OrderBundle\Controller;

use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Event\OrderEvent;
use Oro\Bundle\OrderBundle\Form\Type\OrderType;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\WebsiteBundle\Manager\WebsiteManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * The controller that implement AJAX entry point for Orders.
 */
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
            $order->setWebsite($this->get(WebsiteManager::class)->getDefaultWebsite());
        }

        $form = $this->getType($order);

        $submittedData = $request->get($form->getName());

        $form->submit($submittedData);

        $event = new OrderEvent($form, $form->getData(), $submittedData);
        $this->get(EventDispatcherInterface::class)->dispatch($event, OrderEvent::NAME);

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

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedServices()
    {
        return array_merge(
            parent::getSubscribedServices(),
            [
                WebsiteManager::class,
                EventDispatcherInterface::class,
            ]
        );
    }
}
