<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\Provider;

use Doctrine\ORM\EntityRepository;
use Oro\Bundle\FrontendTestFrameworkBundle\Entity\TestContentVariant;
use Oro\Bundle\ProductBundle\Provider\ProductCollectionContentVariantProvider;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductCollectionContentVariants;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class ProductCollectionContentVariantProviderTest extends WebTestCase
{
    /**
     * @var ProductCollectionContentVariantProvider
     */
    private $provider;

    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([LoadProductCollectionContentVariants::class]);

        $this->provider = new ProductCollectionContentVariantProvider();
    }

    public function testItReturnsProperProductIds()
    {
        $testProduct1 = $this->getReference(LoadProductData::PRODUCT_1);
        $testProduct2 = $this->getReference(LoadProductData::PRODUCT_2);

        $productCollectionTestVariant = $this->getReference(
            LoadProductCollectionContentVariants::PRODUCT_COLLECTION_TEST_VARIANT
        );
        $testVariantWithoutSegment = $this->getReference(
            LoadProductCollectionContentVariants::TEST_VARIANT_WITHOUT_SEGMENT
        );

        /** @var EntityRepository $repository */
        $repository = $this->getContainer()
            ->get('doctrine')
            ->getManagerForClass(TestContentVariant::class)
            ->getRepository(TestContentVariant::class);
        $qb = $repository->createQueryBuilder('variant')
            ->orderBy('variant.id', 'ASC');
        $this->provider->modifyNodeQueryBuilderByEntities(
            $qb,
            null,
            [$testProduct1, $testProduct2]
        );

        $result = $qb->getQuery()->getScalarResult();
        $expectedResult = [
            'product1 in productCollectionVariant' => [
                'variant_id' => $productCollectionTestVariant->getId(),
                'productCollectionProductId' => $testProduct1->getId(),
                'sortOrderValue' => 0.1,
            ],
            'product2 in productCollectionVariant' => [
                'variant_id' => $productCollectionTestVariant->getId(),
                'productCollectionProductId' => $testProduct2->getId(),
                'sortOrderValue' => 0.2
            ],
            'no product in variantWithoutSegment' => [
                'variant_id' => $testVariantWithoutSegment->getId(),
                'productCollectionProductId' => null,
                'sortOrderValue' => null
            ],
        ];

        foreach ($expectedResult as $expectedRowTitle => $expectedRow) {
            static::assertContainsEquals(
                $expectedRow,
                $result,
                "Expected row title - '{$expectedRowTitle}', got: " . \var_export($result, true)
            );
        }
    }
}
