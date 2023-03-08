<?php

namespace Oro\Bundle\ProductBundle\Form\DataTransformer;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductKitItemProduct;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductRepository;
use Symfony\Component\Form\DataTransformerInterface;

/**
 * Form data transformer that transforms Collection<ProductKitItemProduct> into the normalized array and vice-versa.
 */
class ProductKitItemProductsDataTransformer implements DataTransformerInterface
{
    private ManagerRegistry $managerRegistry;

    /** @var Collection<ProductKitItemProduct>|null */
    private ?Collection $kitItemProducts = null;

    public function __construct(ManagerRegistry $managerRegistry)
    {
        $this->managerRegistry = $managerRegistry;
    }

    /**
     * @param Collection<ProductKitItemProduct>|null $value
     *
     * @return array<array{productId: int, sortOrder: int}>|null
     *  [
     *      ['productId' => 42, 'sortOrder' => 11],
     *      // ...
     *  ]
     */
    public function transform($value): ?array
    {
        if (!$value instanceof Collection) {
            return null;
        }

        $this->kitItemProducts = $value;

        $result = [];
        foreach ($this->kitItemProducts as $kitItemProduct) {
            if ($kitItemProduct->getProduct()) {
                $result[] = [
                    'productId' => $kitItemProduct->getProduct()->getId(),
                    'sortOrder' => $kitItemProduct->getSortOrder()
                ];
            }
        }

        return $result;
    }

    /**
     * @param array<array{productId: int, sortOrder: int}>|null $value
     *  [
     *      ['productId' => 42, 'sortOrder' => 11],
     *      // ...
     *  ]
     *
     * @return Collection<ProductKitItemProduct>|null
     */
    public function reverseTransform($value): ?Collection
    {
        if (!is_array($value)) {
            return null;
        }

        if (!$this->kitItemProducts) {
            $this->kitItemProducts = new ArrayCollection();
        }

        $productIds = array_column($value, 'productId');
        if (!$productIds) {
            return new ArrayCollection();
        }

        $productsById = $this->findProducts($productIds);
        $sortOrderById = array_column($value, 'sortOrder', 'productId');

        $kitItemProductsByProductId = [];
        foreach ($this->kitItemProducts as $kitItemProduct) {
            $productId = $kitItemProduct->getProduct()->getId();
            $kitItemProductsByProductId[$productId] = $kitItemProduct;
            if (!isset($productsById[$productId])) {
                $this->kitItemProducts->removeElement($kitItemProduct);
            } else {
                $kitItemProduct->setSortOrder((int)($sortOrderById[$productId] ?? 0));
            }
        }

        $add = array_diff_key($productsById, $kitItemProductsByProductId);
        foreach ($add as $productId => $product) {
            $this->kitItemProducts->add(
                (new ProductKitItemProduct())
                    ->setProduct($product)
                    ->setSortOrder((int)($sortOrderById[$productId] ?? 0))
            );
        }

        return $this->kitItemProducts;
    }

    private function findProducts(array $productIds): array
    {
        /** @var EntityManager $entityManager */
        $entityManager = $this->managerRegistry->getManagerForClass(Product::class);

        /** @var ProductRepository $productRepo */
        $productRepo = $entityManager->getRepository(Product::class);

        $productsByIds = array_replace(
            array_flip($productIds),
            $productRepo
                ->getProductsQueryBuilder($productIds)
                ->indexBy('p', 'p.id')
                ->getQuery()
                ->getResult()
        );

        foreach ($productsByIds as $id => $value) {
            if (!($value instanceof Product)) {
                $productsByIds[$id] = $entityManager->getReference(Product::class, $id);
            }
        }

        return $productsByIds;
    }
}
