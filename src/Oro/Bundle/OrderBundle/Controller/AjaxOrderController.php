<?php

namespace Oro\Bundle\OrderBundle\Controller;

use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Event\OrderEvent;
use Oro\Bundle\OrderBundle\Form\Type\OrderType;
use Oro\Bundle\OrderBundle\Form\Type\SubOrderType;
use Oro\Bundle\OrderBundle\RequestHandler\OrderRequestHandler;
use Oro\Bundle\SecurityBundle\Attribute\AclAncestor;
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
     *
     * @param Request $request
     * @param Order|null $order
     * @return JsonResponse
     */
    #[Route(path: '/entry-point/{id}', name: 'oro_order_entry_point', defaults: ['id' => 0])]
    #[AclAncestor('oro_order_update')]
    public function entryPointAction(Request $request, Order $order = null)
    {
        if (!$order) {
            $order = new Order();
            $order->setWebsite($this->container->get(WebsiteManager::class)->getDefaultWebsite());
        }

        $orderRequestHandler = $this->container->get(OrderRequestHandler::class);
        $order->setCustomer($orderRequestHandler->getCustomer());
        $order->setCustomerUser($orderRequestHandler->getCustomerUser());

        $form = $this->getType($order);

        $submittedData = $request->get($form->getName());

        $form->submit($submittedData);

        $event = new OrderEvent($form, $form->getData(), $submittedData);
        $this->container->get(EventDispatcherInterface::class)->dispatch($event, OrderEvent::NAME);

        return new JsonResponse($event->getData());
    }

    #[Route(path: '/suborder-entry-point/{id}', name: 'oro_suborder_entry_point', defaults: ['id' => 0])]
    #[AclAncestor('oro_order_update')]
    public function suborderEntryPointAction(Request $request, Order $order = null)
    {
        if (!$order) {
            $order = new Order();
            $order->setWebsite($this->container->get(WebsiteManager::class)->getDefaultWebsite());
        }

        $form = $this->createForm(SubOrderType::class, $order, ['validation_groups' => ['order_entry_point']]);

        $submittedData = $request->get($form->getName());

        $form->submit($submittedData);

        $event = new OrderEvent($form, $form->getData(), $submittedData);
        $this->container->get(EventDispatcherInterface::class)->dispatch($event, OrderEvent::NAME);

        return new JsonResponse($event->getData());
    }

    /**
     * @param Order $order
     * @return Form
     */
    protected function getType(Order $order)
    {
        return $this->createForm(OrderType::class, $order, ['validation_groups' => ['order_entry_point']]);
    }

    #[\Override]
    public static function getSubscribedServices(): array
    {
        return array_merge(
            parent::getSubscribedServices(),
            [
                WebsiteManager::class,
                EventDispatcherInterface::class,
                OrderRequestHandler::class,
            ]
        );
    }
}
