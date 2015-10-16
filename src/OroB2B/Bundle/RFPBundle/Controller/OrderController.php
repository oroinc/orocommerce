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
            'withOffers' => 1
        ];
        foreach ($request->getRequestProducts() as $lineItem) {
            $offers = [];
            foreach ($lineItem->getRequestProductItems() as $productItem) {
                $offers[] = [
                    'quantity' => $productItem->getQuantity(),
                    'unit' => $productItem->getProductUnitCode(),
                    'currency' => $productItem->getPrice()->getCurrency(),
                    'price' => $productItem->getPrice()->getValue(),
                ];
            }

            $data[ProductDataStorage::ENTITY_ITEMS_DATA_KEY][] = [
                ProductDataStorage::PRODUCT_SKU_KEY => $lineItem->getProduct()->getSku(),
                'comment' => $lineItem->getComment(),
                'offers' => $offers,
            ];
        }
        $storage->set($data);
    }
}
