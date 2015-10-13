<?php

namespace OroB2B\Bundle\RFPBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;

use OroB2B\Bundle\ProductBundle\Storage\ProductDataStorage;
use OroB2B\Bundle\RFPBundle\Entity\Request;

class OrderController extends Controller
{
    /**
     * @Route("/create/{id}", name="orob2b_rfp_request_create_order", requirements={"id"="\d+"})
     * @AclAncestor("orob2b_order_create")
     *
     * @param Request $request
     *
     * @return array
     */
    public function createAction(Request $request)
    {
        $this->saveToStorage($request);
        return $this->redirect(
            $this->generateUrl('orob2b_order_create', [ProductDataStorage::STORAGE_KEY => true])
        );
    }
    /**
     * @param Request $request
     */
    protected function saveToStorage(Request $request)
    {
        /** @var ProductDataStorage $storage */
        $storage = $this->get('orob2b_product.service.product_data_storage');
        $data = [
            ProductDataStorage::ENTITY_DATA_KEY => [
                'accountUser' => $request->getAccountUser()->getId(),
                'account' => $request->getAccount()->getId(),
            ],
        ];
        foreach ($request->getRequestProducts() as $lineItem) {
            $data[ProductDataStorage::ENTITY_ITEMS_DATA_KEY][] = [
                'comment' => $lineItem->getRequest(),
            ];
        }
        $storage->set($data);
    }
}
