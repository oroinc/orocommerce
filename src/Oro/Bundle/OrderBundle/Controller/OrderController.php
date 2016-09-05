<?php

namespace Oro\Bundle\OrderBundle\Controller;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Oro\Bundle\AddressBundle\Entity\AddressType;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\PricingBundle\SubtotalProcessor\TotalProcessorProvider;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Form\Type\OrderType;
use Oro\Bundle\OrderBundle\RequestHandler\OrderRequestHandler;
use Oro\Bundle\OrderBundle\Form\Handler\OrderHandler;
use Oro\Bundle\OrderBundle\Event\OrderEvent;

class OrderController extends AbstractOrderController
{
    /**
     * @Route("/view/{id}", name="orob2b_order_view", requirements={"id"="\d+"})
     * @Template
     * @Acl(
     *      id="orob2b_order_view",
     *      type="entity",
     *      class="OroOrderBundle:Order",
     *      permission="VIEW",
     *      category="orders"
     * )
     *
     * @param Order $order
     *
     * @return array
     */
    public function viewAction(Order $order)
    {
        return [
            'entity' => $order,
            'totals' => $this->getTotalProcessor()->getTotalWithSubtotalsAsArray($order),
        ];
    }

    /**
     * @Route("/info/{id}", name="orob2b_order_info", requirements={"id"="\d+"})
     * @Template
     * @AclAncestor("orob2b_order_view")
     *
     * @param Order $order
     *
     * @return array
     */
    public function infoAction(Order $order)
    {
        $sourceEntity = null;

        if ($order->getSourceEntityClass() && $order->getSourceEntityId()) {
            $sourceEntityManager = $this->get('oro_entity.doctrine_helper');
            $sourceEntity = $sourceEntityManager->getEntity(
                $order->getSourceEntityClass(),
                $order->getSourceEntityId()
            );
        }

        return [
            'order' => $order,
            'sourceEntity' => $sourceEntity
        ];
    }

    /**
     * @Route("/", name="orob2b_order_index")
     * @Template
     * @AclAncestor("orob2b_order_view")
     *
     * @return array
     */
    public function indexAction()
    {
        return [
            'entity_class' => $this->container->getParameter('orob2b_order.entity.order.class'),
        ];
    }

    /**
     * Create order form
     *
     * @Route("/create", name="orob2b_order_create")
     * @Template("OroOrderBundle:Order:update.html.twig")
     * @Acl(
     *      id="orob2b_order_create",
     *      type="entity",
     *      class="OroOrderBundle:Order",
     *      permission="CREATE"
     * )
     *
     * @param Request $request
     * @return array|RedirectResponse
     */
    public function createAction(Request $request)
    {
        $order = new Order();
        $order->setWebsite($this->get('orob2b_website.manager')->getCurrentWebsite());
        return $this->update($order, $request);
    }

    /**
     * Edit order form
     *
     * @Route("/update/{id}", name="orob2b_order_update", requirements={"id"="\d+"})
     * @Template
     * @Acl(
     *      id="orob2b_order_update",
     *      type="entity",
     *      class="OroOrderBundle:Order",
     *      permission="EDIT"
     * )
     *
     * @param Order $order
     *
     * @param Request $request
     * @return array|RedirectResponse
     */
    public function updateAction(Order $order, Request $request)
    {
        return $this->update($order, $request);
    }

    /**
     * @param Order $order
     * @param Request $request
     *
     * @return array|RedirectResponse
     */
    protected function update(Order $order, Request $request)
    {
        if (in_array($request->getMethod(), ['POST', 'PUT'], true)) {
            $order->setAccount($this->getOrderRequestHandler()->getAccount());
            $order->setAccountUser($this->getOrderRequestHandler()->getAccountUser());
        }

        $form = $this->createForm(OrderType::NAME, $order);

        return $this->get('oro_form.model.update_handler')->handleUpdate(
            $order,
            $form,
            function (Order $order) {
                return [
                    'route' => 'orob2b_order_update',
                    'parameters' => ['id' => $order->getId()],
                ];
            },
            function (Order $order) {
                return [
                    'route' => 'orob2b_order_view',
                    'parameters' => ['id' => $order->getId()],
                ];
            },
            $this->get('translator')->trans('oro.order.controller.order.saved.message'),
            null,
            function (Order $order, FormInterface $form, Request $request) {

                $submittedData = $request->get($form->getName(), []);
                $event = new OrderEvent($form, $form->getData(), $submittedData);
                $this->get('event_dispatcher')->dispatch(OrderEvent::NAME, $event);
                $orderData = $event->getData()->getArrayCopy();

                return [
                    'form' => $form->createView(),
                    'entity' => $order,
                    'isWidgetContext' => (bool)$request->get('_wid', false),
                    'isShippingAddressGranted' => $this->getOrderAddressSecurityProvider()
                        ->isAddressGranted($order, AddressType::TYPE_SHIPPING),
                    'isBillingAddressGranted' => $this->getOrderAddressSecurityProvider()
                        ->isAddressGranted($order, AddressType::TYPE_BILLING),
                    'orderData' => $orderData
                ];
            }
        );
    }

    /**
     * @return OrderRequestHandler
     */
    protected function getOrderRequestHandler()
    {
        return $this->get('orob2b_order.request_handler.order_request_handler');
    }

    /**
     * @return TotalProcessorProvider
     */
    protected function getTotalProcessor()
    {
        return $this->get('orob2b_pricing.subtotal_processor.total_processor_provider');
    }
}
