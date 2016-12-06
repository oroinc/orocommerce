<?php

namespace Oro\Bundle\DPDBundle\Controller;

use Oro\Bundle\OrderBundle\Controller\AbstractOrderController;
use Oro\Bundle\OrderBundle\Entity\Order;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

class DPDController extends AbstractOrderController
{
    /**
     * @Route("/view/{id}", name="oro_dpd_view", requirements={"id"="\d+"})
     * @AclAncestor("oro_order_view")
     *
     * @param Order $order
     *
     * @return Response
     */
    public function indexAction(Order $order)
    {
    }
}
