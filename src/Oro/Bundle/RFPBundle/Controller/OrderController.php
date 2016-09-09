<?php

namespace Oro\Bundle\RFPBundle\Controller;

use Doctrine\Common\Util\ClassUtils;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\ProductBundle\Storage\ProductDataStorage;
use Oro\Bundle\RFPBundle\Entity\Request as RFPRequest;
use Oro\Bundle\RFPBundle\Entity\RequestProductItem;

class OrderController extends Controller
{
    /**
     * @Route("/create/{id}", name="oro_rfp_request_create_order", requirements={"id"="\d+"})
     * @AclAncestor("oro_order_create")
     *
     * @param RFPRequest $request
     *
     * @return array
     */
    public function createAction(RFPRequest $request)
    {
        $data = [ProductDataStorage::ENTITY_DATA_KEY => $this->getEntityData($request)];

        $offers = [];
        foreach ($request->getRequestProducts() as $lineItem) {
            $data[ProductDataStorage::ENTITY_ITEMS_DATA_KEY][] = [
                ProductDataStorage::PRODUCT_SKU_KEY => $lineItem->getProduct()->getSku(),
                'comment' => $lineItem->getComment(),
            ];

            $itemOffers = [];
            foreach ($lineItem->getRequestProductItems() as $productItem) {
                $itemOffers[] = $this->getOfferData($productItem);
            }
            $offers[] = $itemOffers;
        }

        $this->get('oro_product.storage.product_data_storage')->set($data);
        $this->get('oro_rfp.storage.offers_data_storage')->set($offers);

        return $this->redirectToRoute('oro_order_create', [ProductDataStorage::STORAGE_KEY => true]);
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

        $data['sourceEntityId'] = $request->getId();
        $data['sourceEntityClass'] = ClassUtils::getClass($request);
        $data['sourceEntityIdentifier'] = $request->getIdentifier();

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
