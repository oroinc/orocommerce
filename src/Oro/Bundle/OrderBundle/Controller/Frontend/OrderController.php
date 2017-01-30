<?php

namespace Oro\Bundle\OrderBundle\Controller\Frontend;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

use Oro\Bundle\LayoutBundle\Annotation\Layout;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\PricingBundle\SubtotalProcessor\TotalProcessorProvider;
use Oro\Bundle\OrderBundle\Controller\AbstractOrderController;
use Oro\Bundle\OrderBundle\Entity\Order;
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
     *      permission="VIEW",
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
