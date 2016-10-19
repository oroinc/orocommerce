<?php

namespace Oro\Bundle\OrderBundle\Controller;

use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Form\Handler\OrderShippingTrackingHandler;
use Oro\Bundle\OrderBundle\Form\Type\OrderShippingTrackingCollectionType;
use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class OrderShippingTrackingController extends Controller
{
    /**
     * Create&Update Order ShippingTrackings
     *
     * @Route("/change/{id}", name="oro_order_shipping_tracking_change", requirements={"id"="\d+"})
     * @Template
     * @Acl(
     *      id="oro_order_shipping_tracking_change",
     *      type="entity",
     *      class="OroOrderBundle:OrderShippingTracking",
     *      permission="EDIT"
     * )
     *
     * @param Order $order
     * @param Request $request
     * @throws AccessDeniedHttpException
     * @throws \LogicException
     * @return array|RedirectResponse
     */
    public function changeAction(Order $order, Request $request)
    {
        if (!$this->get('oro_security.security_facade')->isGranted('EDIT', $order)) {
            throw new AccessDeniedHttpException();
        }

        $form = $this->createForm(
            OrderShippingTrackingCollectionType::class,
            $order->getShippingTrackings()
        );

        $handler = new OrderShippingTrackingHandler(
            $form,
            $this->getDoctrine()->getManagerForClass('OroOrderBundle:Order'),
            $request,
            $order
        );

        $result =  $this->get('oro_form.model.update_handler')->handleUpdate(
            $order,
            $form,
            null,
            null,
            null,
            $handler
        );

        return $result;
    }
}
