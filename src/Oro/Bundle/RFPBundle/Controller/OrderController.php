<?php

namespace Oro\Bundle\RFPBundle\Controller;

use Oro\Bundle\ProductBundle\Storage\ProductDataStorage;
use Oro\Bundle\RFPBundle\Entity\Request as RFPRequest;
use Oro\Bundle\RFPBundle\Entity\RequestProduct;
use Oro\Bundle\RFPBundle\Entity\RequestProductItem;
use Oro\Bundle\RFPBundle\Storage\OffersDataStorage;
use Oro\Bundle\SecurityBundle\Attribute\AclAncestor;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Controller actions to create Order entity using RFQ entity as source.
 */
class OrderController extends AbstractController
{
    #[Route(path: '/create/{id}', name: 'oro_rfp_request_create_order', requirements: ['id' => '\d+'])]
    #[AclAncestor('oro_order_create')]
    public function createAction(RFPRequest $request): Response
    {
        $data = [ProductDataStorage::ENTITY_DATA_KEY => $this->getEntityData($request)];

        $offers = [];
        foreach ($request->getRequestProducts() as $lineItem) {
            $data[ProductDataStorage::ENTITY_ITEMS_DATA_KEY][] = [
                ProductDataStorage::PRODUCT_SKU_KEY => $lineItem->getProductSku(),
                ProductDataStorage::PRODUCT_ID_KEY => $lineItem->getProduct()->getId(),
                'comment' => $lineItem->getComment(),
                ProductDataStorage::PRODUCT_KIT_ITEM_LINE_ITEMS_DATA_KEY => $this->getKitItemLineItemsData($lineItem),
            ];

            $itemOffers = [];
            foreach ($lineItem->getRequestProductItems() as $productItem) {
                $itemOffers[] = $this->getOfferData($productItem);
            }
            $offers[] = $itemOffers;
        }

        $this->container->get(ProductDataStorage::class)->set($data);
        $this->container->get(OffersDataStorage::class)->set($offers);

        return $this->redirectToRoute('oro_order_create', [ProductDataStorage::STORAGE_KEY => true]);
    }

    private function getEntityData(RFPRequest $request): array
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

    private function getOfferData(RequestProductItem $productItem): array
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

    private function getKitItemLineItemsData(RequestProduct $requestProduct): array
    {
        $kitItemLineItemsData = [];
        foreach ($requestProduct->getKitItemLineItems() as $kitItemLineItem) {
            $kitItemLineItemsData[] = [
                ProductDataStorage::PRODUCT_KIT_ITEM_LINE_ITEM_KIT_ITEM_KEY => $kitItemLineItem->getKitItem()?->getId(),
                ProductDataStorage::PRODUCT_KIT_ITEM_LINE_ITEM_PRODUCT_KEY => $kitItemLineItem->getProduct()?->getId(),
                ProductDataStorage::PRODUCT_KIT_ITEM_LINE_ITEM_PRODUCT_UNIT_KEY =>
                    $kitItemLineItem->getProductUnit()?->getCode(),
                ProductDataStorage::PRODUCT_KIT_ITEM_LINE_ITEM_QUANTITY_KEY => $kitItemLineItem->getQuantity(),
            ];
        }

        return $kitItemLineItemsData;
    }

    /**
     * {@inheritDoc}
     */
    public static function getSubscribedServices(): array
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
