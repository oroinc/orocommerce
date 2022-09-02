<?php

namespace Oro\Bundle\CatalogBundle\Tests\Functional\Provider;

use Doctrine\ORM\EntityRepository;
use Oro\Bundle\CatalogBundle\Provider\ProductsContentVariantProvider;
use Oro\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryContentVariants;
use Oro\Bundle\FrontendTestFrameworkBundle\Entity\TestContentVariant;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class ProductsContentVariantProviderTest extends WebTestCase
{
    /** @var ProductsContentVariantProvider */
    private $provider;

    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([LoadCategoryContentVariants::class]);

        $this->provider = new ProductsContentVariantProvider();
    }

    public function testItReturnsProperProductIds()
    {
        /** @var Product $productInFirstLevel */
        $productInFirstLevel = $this->getReference(LoadProductData::PRODUCT_1);
        /** @var Product $productInSecondLevel1 */
        $productInSecondLevel1 = $this->getReference(LoadProductData::PRODUCT_2);
        /** @var Product $productInSecondLevel2 */
        $productInSecondLevel2 = $this->getReference(LoadProductData::PRODUCT_5);
        /** @var Product $productInForthLevel */
        $productInForthLevel = $this->getReference(LoadProductData::PRODUCT_6);

        /** @var TestContentVariant $variantWithFirstLevel */
        $variantWithFirstLevel = $this->getReference('test_category_variant.1');
        /** @var TestContentVariant $variantWithSecondLevel1 */
        $variantWithSecondLevel1 = $this->getReference('test_category_variant.2');
        /** @var TestContentVariant $variantWithSecondLevel2 */
        $variantWithSecondLevel2 = $this->getReference('test_category_variant.3');
        /** @var TestContentVariant $variantWithForthLevel */
        $variantWithForthLevel = $this->getReference('test_category_variant.4');

        /** @var EntityRepository $repository */
        $repository = $this->getContainer()
            ->get('doctrine')
            ->getManagerForClass(TestContentVariant::class)
            ->getRepository(TestContentVariant::class);

        $qb = $repository->createQueryBuilder('variant')
            ->orderBy('variant.id', 'ASC')
            ->addOrderBy('categoryProductId', 'ASC');

        $this->provider->modifyNodeQueryBuilderByEntities(
            $qb,
            null,
            [
                $productInFirstLevel,
                $productInSecondLevel1,
                $productInSecondLevel2,
                $productInForthLevel
            ]
        );
        $result = $qb->getQuery()->getScalarResult();
        $result = array_values(array_filter($result, function ($value) {
            return (bool) $value['categoryProductId'];
        }));

        $this->assertCount(8, $result);
        $expectedResult = [
            [
                'variant_id' => $variantWithFirstLevel->getId(),
                'categoryProductId' => $productInFirstLevel->getId(),
            ],
            [
                'variant_id' => $variantWithFirstLevel->getId(),
                'categoryProductId' => $productInSecondLevel1->getId(),
            ],
            [
                'variant_id' => $variantWithFirstLevel->getId(),
                'categoryProductId' => $productInSecondLevel2->getId(),
            ],
            [
                'variant_id' => $variantWithFirstLevel->getId(),
                'categoryProductId' => $productInForthLevel->getId(),
            ],
            [
                'variant_id' => $variantWithSecondLevel1->getId(),
                'categoryProductId' => $productInSecondLevel1->getId(),
            ],
            [
                'variant_id' => $variantWithSecondLevel1->getId(),
                'categoryProductId' => $productInForthLevel->getId(),
            ],
            [
                'variant_id' => $variantWithSecondLevel2->getId(),
                'categoryProductId' => $productInSecondLevel2->getId(),
            ],
            [
                'variant_id' => $variantWithForthLevel->getId(),
                'categoryProductId' => $productInForthLevel->getId(),
            ],
        ];
        $this->assertEquals($expectedResult, $result);
    }
}
