<?php

declare(strict_types=1);

namespace Oro\Bundle\ProductBundle\Form\Handler;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\FormBundle\Form\Handler\FormHandlerInterface;
use Oro\Bundle\FormBundle\Form\Handler\RequestHandlerTrait;
use Oro\Bundle\ProductBundle\Entity\CollectionSortOrder;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Handler\CollectionSortOrderHandler;
use Oro\Bundle\ProductBundle\Service\ProductCollectionSegmentManipulator;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Form handler for {@see ProductCollectionSegmentProductsType} form type.
 * Updates included/excluded products of the specified segment.
 * Updates products sort order.
 */
class ProductCollectionSegmentProductsFormHandler implements FormHandlerInterface
{
    use RequestHandlerTrait;

    private ManagerRegistry $managerRegistry;

    private ProductCollectionSegmentManipulator $productCollectionSegmentManipulator;

    private CollectionSortOrderHandler $collectionSortOrderHandler;

    public function __construct(
        ManagerRegistry $managerRegistry,
        ProductCollectionSegmentManipulator $productCollectionSegmentManipulator,
        CollectionSortOrderHandler $collectionSortOrderHandler
    ) {
        $this->managerRegistry = $managerRegistry;
        $this->productCollectionSegmentManipulator = $productCollectionSegmentManipulator;
        $this->collectionSortOrderHandler = $collectionSortOrderHandler;
    }

    public function process($data, FormInterface $form, Request $request): bool
    {
        if (!$data instanceof Segment) {
            throw new \TypeError(
                sprintf(
                    '"%s()" expects parameter 1 to be an instance of "%s", "%s" given.',
                    __METHOD__,
                    Segment::class,
                    get_debug_type($data)
                )
            );
        }

        if ($request->getMethod() === Request::METHOD_PUT) {
            $this->submitPostPutRequest($form, $request);

            if ($form->isValid()) {
                $appendProductsId = $this->getIdsFromProducts($form->get('appendProducts')->getData());
                $removeProductsIds = $this->getIdsFromProducts($form->get('removeProducts')->getData());

                [, $excludedProductIds] = $this->productCollectionSegmentManipulator
                    ->updateManuallyManagedProducts($data, $appendProductsId, $removeProductsIds);

                $this->processSortOrders($form, $data, $excludedProductIds);

                $entityManager = $this->managerRegistry->getManagerForClass(Segment::class);
                $entityManager->persist($data);
                $entityManager->flush();

                return true;
            }
        }

        return false;
    }

    private function processSortOrders(FormInterface $form, Segment $segment, array $excludedProductsIds): void
    {
        $sortOrderData = $form->get('sortOrder')->getData();
        $sortOrderEntities = [];
        foreach ($sortOrderData as $productId => $sortOrder) {
            /** @var CollectionSortOrder $sortOrderEntity */
            $sortOrderEntity = $sortOrder['data'];
            if (in_array($productId, $excludedProductsIds, false)) {
                // Sort order for the removed product must be unset.
                $sortOrderEntity->setSortOrder(null);
            }

            $sortOrderEntities[] = $sortOrderEntity;
        }

        $this->collectionSortOrderHandler->updateSegmentSortOrders($sortOrderEntities, $segment);
    }

    private function getIdsFromProducts(array $products): array
    {
        return array_map(static fn (Product $product) => $product->getId(), $products);
    }
}
