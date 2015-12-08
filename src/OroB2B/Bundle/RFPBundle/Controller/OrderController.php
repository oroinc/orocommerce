<?php

namespace OroB2B\Bundle\RFPBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;

use OroB2B\Bundle\ProductBundle\Storage\ProductDataStorage;
use OroB2B\Bundle\RFPBundle\Entity\Request as RFPRequest;
use OroB2B\Bundle\RFPBundle\Entity\RequestProductItem;
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
        $data = [ProductDataStorage::ENTITY_DATA_KEY => $this->getEntityData($request)];

        foreach ($request->getRequestProducts() as $lineItem) {
            $data[ProductDataStorage::ENTITY_ITEMS_DATA_KEY][] = [
                ProductDataStorage::PRODUCT_SKU_KEY => $lineItem->getProduct()->getSku(),
                'comment' => $lineItem->getComment(),
            ];

            $offers = [];
            foreach ($lineItem->getRequestProductItems() as $productItem) {
                $offers[] = $this->getOfferData($productItem);
            }

            $data[OrderDataStorageExtension::OFFERS_DATA_KEY][] = $offers;
        }

        $this->get('orob2b_product.service.product_data_storage')->set($data);

        return $this->redirectToRoute('orob2b_order_create', [ProductDataStorage::STORAGE_KEY => true]);
    }

    /**
     * @param RFPRequest $request
     * @return array
     */
    protected function getEntityData(RFPRequest $request)
    {
        $data = [];

        if ($request->getAccountUser()) {
            $data['accountUser'] = $request->getAccountUser()->getId();
        }

        if ($request->getAccount()) {
            $data['account'] = $request->getAccount()->getId();
        }

        return $data;
    }

    /**
     * @param RequestProductItem $productItem
     * @return array
     */
    protected function getOfferData(RequestProductItem $productItem)
    {
        $data = [
            'quantity' => $productItem->getQuantity(),
            'unit' => $productItem->getProductUnitCode(),
        ];

        $price = $productItem->getPrice();
        if ($price) {
            $data['currency'] = $price->getCurrency();
            $data['price'] = $price->getValue();
        }

        return $data;
    }
}
