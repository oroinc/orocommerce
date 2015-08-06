<?php

namespace OroB2B\Bundle\OrderBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\SecurityBundle\Annotation\Acl;

use OroB2B\Bundle\OrderBundle\Entity\Order;
use OroB2B\Bundle\OrderBundle\Form\Type\OrderType;
use OroB2B\Bundle\OrderBundle\Model\Subtotal;

class OrderController extends Controller
{
    /**
     * @Route("/view/{id}", name="orob2b_order_view", requirements={"id"="\d+"})
     * @Template
     * @Acl(
     *      id="orob2b_order_view",
     *      type="entity",
     *      class="OroB2BOrderBundle:Order",
     *      permission="VIEW"
     * )
     *
     * @param Order $order
     *
     * @return array
     */
    public function viewAction(Order $order)
    {
        return [
            'entity' => $order
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
        return [
            'order' => $order
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
            'entity_class' => $this->container->getParameter('orob2b_order.entity.order.class')
        ];
    }

    /**
     * Create order form
     *
     * @Route("/create", name="orob2b_order_create")
     * @Template("OroB2BOrderBundle:Order:update.html.twig")
     * @Acl(
     *      id="orob2b_order_create",
     *      type="entity",
     *      class="OroB2BOrderBundle:Order",
     *      permission="CREATE"
     * )
     *
     * @return array|RedirectResponse
     */
    public function createAction()
    {
        return $this->update(new Order());
    }

    /**
     * Edit order form
     *
     * @Route("/update/{id}", name="orob2b_order_update", requirements={"id"="\d+"})
     * @Template
     * @Acl(
     *      id="orob2b_order_update",
     *      type="entity",
     *      class="OroB2BOrderBundle:Order",
     *      permission="EDIT"
     * )
     *
     * @param Order $order
     *
     * @return array|RedirectResponse
     */
    public function updateAction(Order $order)
    {
        return $this->update($order);
    }

    /**
     * Get subtotals for new order
     *
     * @Route("/subtotals", name="orob2b_order_create_subtotals")
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
    public function createSubtotalsAction()
    {
        return $this->subtotals(new Order());
    }

    /**
     * Get order subtotals
     *
     * @Route("/subtotals/{id}", name="orob2b_order_subtotals", requirements={"id"="\d+"})
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
    public function subtotalsAction(Order $order)
    {
        return $this->subtotals($order);
    }

    /**
     * @param Order $order
     *
     * @return JsonResponse
     */
    protected function subtotals(Order $order)
    {
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

    /**
     * @param Order $order
     *
     * @return array|RedirectResponse
     */
    protected function update(Order $order)
    {
        return $this->get('oro_form.model.update_handler')->handleUpdate(
            $order,
            $this->createForm(OrderType::NAME, $order),
            function (Order $order) {
                return [
                    'route'      => 'orob2b_order_update',
                    'parameters' => ['id' => $order->getId()]
                ];
            },
            function (Order $order) {
                return [
                    'route'      => 'orob2b_order_view',
                    'parameters' => ['id' => $order->getId()]
                ];
            },
            $this->get('translator')->trans('orob2b.order.controller.order.saved.message')
        );
    }
}
