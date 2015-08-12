<?php

namespace OroB2B\Bundle\OrderBundle\Controller\Frontend;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\SecurityBundle\Annotation\Acl;

use OroB2B\Bundle\OrderBundle\Entity\Order;

class OrderController extends Controller
{
    /**
     * @Route("/", name="orob2b_order_frontend_index")
     * @Template("OroB2BOrderBundle:Order/Frontend:index.html.twig")
     *
     * @Acl(
     *      id="orob2b_order_frontend_index",
     *      type="entity",
     *      class="OroB2BOrderBundle:Order",
     *      permission="VIEW",
     *      group_name="commerce"
     * )
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
     * @Route("/view/{id}", name="orob2b_order_frontend_view", requirements={"id"="\d+"})
     * @Template("OroB2BOrderBundle:Order/Frontend:view.html.twig")
     *
     * @Acl(
     *      id="orob2b_order_frontend_view",
     *      type="entity",
     *      class="OroB2BOrderBundle:Order",
     *      permission="ACCOUNT_VIEW",
     *      group_name="commerce"
     * )
     *
     * @param Order $entity
     * @return array
     */
    public function viewAction(Order $entity)
    {
        return [
            'entity' => $entity
        ];
    }

    /**
     * @Route("/info/{id}", name="orob2b_order_frontend_info", requirements={"id"="\d+"})
     * @Template("OroB2BOrderBundle:Order/Frontend/widget:info.html.twig")
     *
     * @AclAncestor("orob2b_order_frontend_view")
     *
     * @param Order $entity
     * @return array
     */
    public function infoAction(Order $entity)
    {
        return [
            'entity' => $entity
        ];
    }
}
