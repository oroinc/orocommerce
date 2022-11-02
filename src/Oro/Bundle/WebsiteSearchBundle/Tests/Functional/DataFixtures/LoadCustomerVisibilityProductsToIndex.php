<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\ReferenceRepository;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\TestFrameworkBundle\Entity\TestProduct;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class LoadCustomerVisibilityProductsToIndex extends AbstractFixture implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    /**
     * The count of records that the elastic search engine return by default is equal 10.
     * Therefore, add products for 1 more than default.
     */
    public const PRODUCT_COUNT = 11;

    private const REFERENCE_PRODUCT_PREFIX = 'product';

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        for ($productId = 1; $productId <= self::PRODUCT_COUNT; $productId++) {
            $productReference = self::getReferenceName($productId);
            $product = new TestProduct();
            $product->setName($productReference);
            $manager->persist($product);

            $this->addReference($productReference, $product);
        }

        $manager->flush();
        $manager->clear();
    }

    /**
     * @param ReferenceRepository $referenceRepository
     *
     * @return int[]
     */
    public static function getProductIds(ReferenceRepository $referenceRepository): array
    {
        $products = [];
        for ($productId = 1; $productId <= self::PRODUCT_COUNT; $productId++) {
            $productReference = self::getReferenceName($productId);
            /** @var TestProduct[] $products */
            $products[] = $referenceRepository->getReference($productReference);
        }

        return array_map(fn (TestProduct $product) => $product->getId(), $products);
    }

    private static function getReferenceName(int $productId): string
    {
        return sprintf('%s_%s', self::REFERENCE_PRODUCT_PREFIX, $productId);
    }
}
