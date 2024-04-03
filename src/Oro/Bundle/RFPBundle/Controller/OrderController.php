<?php

namespace Oro\Bundle\RFPBundle\Controller;

use Oro\Bundle\ProductBundle\Storage\ProductDataStorage;
use Oro\Bundle\RFPBundle\Entity\Request as RFPRequest;
use Oro\Bundle\RFPBundle\Entity\RequestProductItem;
use Oro\Bundle\RFPBundle\Storage\RequestToOrderDataStorage;
use Oro\Bundle\SecurityBundle\Attribute\AclAncestor;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Controller actions to create Order entity using RFQ entity as a source.
 */
class OrderController extends AbstractController
{
    #[Route(path: '/create/{id}', name: 'oro_rfp_request_create_order', requirements: ['id' => '\d+'])]
    #[AclAncestor('oro_order_create')]
    public function createAction(RFPRequest $request): Response
    {
        /** @var RequestToOrderDataStorage $storage */
        $storage = $this->container->get(RequestToOrderDataStorage::class);
        $storage->saveToStorage($request);

        return $this->redirectToRoute('oro_order_create', [ProductDataStorage::STORAGE_KEY => true]);
    }

    /**
     * @deprecated, use {@see RequestToOrderDataStorage} instead.
     *
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
     * @deprecated, use {@see RequestToOrderDataStorage} instead.
     *
     * @param RequestProductItem $productItem
     * @return array
     */
    protected function getOfferData(RequestProductItem $productItem): array
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

    public static function getSubscribedServices(): array
    {
        return array_merge(parent::getSubscribedServices(), [RequestToOrderDataStorage::class]);
    }
}
