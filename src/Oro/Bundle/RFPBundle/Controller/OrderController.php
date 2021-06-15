<?php

namespace Oro\Bundle\RFPBundle\Controller;

use Oro\Bundle\ProductBundle\Storage\ProductDataStorage;
use Oro\Bundle\RFPBundle\Entity\Request as RFPRequest;
use Oro\Bundle\RFPBundle\Entity\RequestProductItem;
use Oro\Bundle\RFPBundle\Storage\OffersDataStorage;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Controller actions to create Order entity using RFQ entity as source.
 */
class OrderController extends AbstractController
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
                ProductDataStorage::PRODUCT_SKU_KEY => $lineItem->getProductSku(),
                'comment' => $lineItem->getComment(),
            ];

            $itemOffers = [];
            foreach ($lineItem->getRequestProductItems() as $productItem) {
                $itemOffers[] = $this->getOfferData($productItem);
            }
            $offers[] = $itemOffers;
        }

        $this->get(ProductDataStorage::class)->set($data);
        $this->get(OffersDataStorage::class)->set($offers);

        return $this->redirectToRoute('oro_order_create', [ProductDataStorage::STORAGE_KEY => true]);
    }

    /**
     * @param RFPRequest $request
     * @return array
     */
    protected function getEntityData(RFPRequest $request)
    {
        $data = [];

        if ($request->getCustomerUser()) {
            $data['customerUser'] = $request->getCustomerUser()->getId();
        }

        if ($request->getCustomer()) {
            $data['customer'] = $request->getCustomer()->getId();
        }

        $data['shipUntil'] = $request->getShipUntil();
        $data['poNumber'] = $request->getPoNumber();
        $data['customerNotes'] = $request->getNote();
        $data['sourceEntityId'] = $request->getId();
        $data['sourceEntityClass'] = get_class($request);
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

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedServices()
    {
        return array_merge(
            parent::getSubscribedServices(),
            [
                ProductDataStorage::class,
                OffersDataStorage::class,
            ]
        );
    }
}
