<?php

namespace OroB2B\Bundle\RFPBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;

use OroB2B\Bundle\ProductBundle\Storage\ProductDataStorage;
use OroB2B\Bundle\RFPBundle\Entity\Request as RFPRequest;

class OrderController extends Controller
{
    /**
     * @Route("/create/{id}", name="orob2b_rfp_request_create_order", requirements={"id"="\d+"})
     * @AclAncestor("orob2b_order_create")
     *
     * @param RFPRequest $request
     *
     * @return array
     */
    public function createAction(RFPRequest $request)
    {
        $this->get('orob2b_rfp.service.product_data_storage')->saveToStorage($request);

        return $this->redirectToRoute('orob2b_order_create', [ProductDataStorage::STORAGE_KEY => true]);
    }
}
