<?php

namespace OroB2B\Bundle\RFPBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;

use OroB2B\Bundle\ProductBundle\Storage\ProductDataStorage;
use OroB2B\Bundle\RFPBundle\Entity\Request as RFPRequest;
use OroB2B\Bundle\RFPBundle\Form\Extension\OrderDataStorageExtension;

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
        $data = [
            ProductDataStorage::ENTITY_DATA_KEY => [
                'accountUser' => $request->getAccountUser()->getId(),
                'account' => $request->getAccount()->getId(),
            ],
        ];

        foreach ($request->getRequestProducts() as $lineItem) {
            $data[ProductDataStorage::ENTITY_ITEMS_DATA_KEY][] = [
                ProductDataStorage::PRODUCT_SKU_KEY => $lineItem->getProduct()->getSku(),
                'comment' => $lineItem->getComment(),
            ];

            $offers = [];
            foreach ($lineItem->getRequestProductItems() as $productItem) {
                $offers[] = [
                    'quantity' => $productItem->getQuantity(),
                    'unit' => $productItem->getProductUnit()->getCode(),
                    'currency' => $productItem->getPrice() ? $productItem->getPrice()->getCurrency() : null,
                    'price' => $productItem->getPrice() ? $productItem->getPrice()->getValue() : 0,
                ];
            }

            $data[OrderDataStorageExtension::OFFERS_DATA_KEY][] = $offers;
        }

        $this->get('orob2b_product.service.product_data_storage')->set($data);

        return $this->redirectToRoute('orob2b_order_create', [ProductDataStorage::STORAGE_KEY => true]);
    }
}
