<?php

namespace Oro\Bundle\OrderBundle\Controller\Frontend;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Oro\Bundle\AddressBundle\Entity\AddressType;
use Oro\Bundle\LayoutBundle\Annotation\Layout;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\PricingBundle\SubtotalProcessor\TotalProcessorProvider;
use Oro\Bundle\OrderBundle\Controller\AbstractOrderController;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Form\Type\FrontendOrderType;
use Oro\Bundle\OrderBundle\Event\OrderEvent;
use Oro\Bundle\OrderBundle\RequestHandler\FrontendOrderDataHandler;

class OrderController extends AbstractOrderController
{
    /**
     * @Route("/", name="oro_order_frontend_index")
     * @Layout(vars={"entity_class"})
     * @Acl(
     *      id="oro_order_frontend_view",
     *      type="entity",
     *      class="OroOrderBundle:Order",
     *      permission="ACCOUNT_VIEW",
     *      group_name="commerce"
     * )
     *
     * @return array
     */
    public function indexAction()
    {
        return [
            'entity_class' => $this->container->getParameter('oro_order.entity.order.class'),
        ];
    }
    
    /**
     * @Route("/view/{id}", name="oro_order_frontend_view", requirements={"id"="\d+"})
     * @AclAncestor("oro_order_frontend_view")
     * @Layout()
     *
     * @param Order $order
     * @return array
     */
    public function viewAction(Order $order)
    {
        return [
            'data' => [
                'order' => $order,
                'totals' => (object)$this->getTotalProcessor()->getTotalWithSubtotalsAsArray($order),
            ],
        ];
    }

    /**
     * @Route("/info/{id}", name="oro_order_frontend_info", requirements={"id"="\d+"})
     * @Template("OroOrderBundle:Order/Frontend:info.html.twig")
     * @AclAncestor("oro_order_frontend_view")
     *
     * @param Order $order
     * @return array
     */
    public function infoAction(Order $order)
    {
        return [
            'order' => $order,
        ];
    }

    /*
     * Should be fixed in scope of task BB-3686
     */
//    /**
//     * Create order form
//     *
//     * @Route("/create", name="oro_order_frontend_create")
//     * @Template("OroOrderBundle:Order/Frontend:update.html.twig")
//     * @Acl(
//     *      id="oro_order_frontend_create",
//     *      type="entity",
//     *      class="OroOrderBundle:Order",
//     *      permission="CREATE",
//     *      group_name="commerce"
//     * )
//     *
//     * @return array|RedirectResponse
//     */
//    public function createAction()
//    {
//        $order = new Order();
//        $order->setWebsite($this->get('oro_website.manager')->getCurrentWebsite());
//
//        return $this->update($order);
//    }

    /*
     * Should be fixed in scope of task BB-3686
     */
//    /**
//     * Edit order form
//     *
//     * @Route("/update/{id}", name="oro_order_frontend_update", requirements={"id"="\d+"})
//     * @Template("OroOrderBundle:Order/Frontend:update.html.twig")
//     * @Acl(
//     *      id="oro_order_frontend_update",
//     *      type="entity",
//     *      class="OroOrderBundle:Order",
//     *      permission="EDIT",
//     *      group_name="commerce"
//     * )
//     *
//     * @param Order $order
//     * @return array|RedirectResponse
//     */
//    public function updateAction(Order $order)
//    {
//        return $this->update($order);
//    }

    /**
     * Success order
     *
     * @Route("/success/{id}", name="oro_order_frontend_success", requirements={"id"="\d+"})
     * @Layout()
     * @Acl(
     *      id="oro_order_view",
     *      type="entity",
     *      class="OroOrderBundle:Order",
     *      permission="EDIT"
     * )
     *
     * @param Order $order
     *
     * @return array
     */
    public function successAction(Order $order)
    {
        return [
            'data' => [
                'order' => $order,
            ],
        ];
    }

    /*
     * Should be fixed in scope of task BB-3686
     */
//    /**
//     * @param Order $order
//     *
//     * @return array|RedirectResponse
//     */
//    protected function update(Order $order)
//    {
//        $order->setAccountUser($this->getFrontendOrderDataHandler()->getAccountUser());
//        $order->setAccount($this->getFrontendOrderDataHandler()->getAccount());
//        $order->setPaymentTerm($this->getFrontendOrderDataHandler()->getPaymentTerm());
//        $order->setOwner($this->getFrontendOrderDataHandler()->getOwner());
//
//        $form = $this->createForm(FrontendOrderType::NAME, $order);
//
//        return $this->get('oro_form.model.update_handler')->handleUpdate(
//            $order,
//            $form,
//            function (Order $order) {
//                return [
//                    'route' => 'oro_order_frontend_update',
//                    'parameters' => ['id' => $order->getId()],
//                ];
//            },
//            function (Order $order) {
//                return [
//                    'route' => 'oro_order_frontend_view',
//                    'parameters' => ['id' => $order->getId()],
//                ];
//            },
//            $this->get('translator')->trans('oro.order.controller.order.saved.message'),
//            null,
//            function (Order $order, FormInterface $form, Request $request) {
//
//                $submittedData = $request->get($form->getName(), []);
//                $event = new OrderEvent($form, $form->getData(), $submittedData);
//                $this->get('event_dispatcher')->dispatch(OrderEvent::NAME, $event);
//                $orderData = $event->getData()->getArrayCopy();
//
//                return [
//                    'form' => $form->createView(),
//                    'entity' => $order,
//                    'isWidgetContext' => (bool)$request->get('_wid', false),
//                    'isShippingAddressGranted' => $this->getOrderAddressSecurityProvider()
//                        ->isAddressGranted($order, AddressType::TYPE_SHIPPING),
//                    'isBillingAddressGranted' => $this->getOrderAddressSecurityProvider()
//                        ->isAddressGranted($order, AddressType::TYPE_BILLING),
//                    'orderData' => $orderData
//                ];
//            }
//        );
//    }

    /**
     * @return TotalProcessorProvider
     */
    protected function getTotalProcessor()
    {
        return $this->get('oro_pricing.subtotal_processor.total_processor_provider');
    }

    /**
     * @return FrontendOrderDataHandler
     */
    protected function getFrontendOrderDataHandler()
    {
        return $this->get('oro_order.request_handler.frontend_order_data_handler');
    }
}
