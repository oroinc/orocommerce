<?php

namespace Oro\Bundle\CatalogBundle\Tests\Functional\Provider;

use Doctrine\ORM\EntityRepository;

use Oro\Bundle\CatalogBundle\Provider\ProductsContentVariantProvider;
use Oro\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryContentVariants;
use Oro\Bundle\FrontendTestFrameworkBundle\Entity\TestContentVariant;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\ProductBundle\Tests\Unit\Entity\Stub\Product;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class ProductsContentVariantProviderTest extends WebTestCase
{
    /** @var ProductsContentVariantProvider */
    private $provider;

    protected function setUp()
    {
        $this->initClient();
        $this->loadFixtures([LoadCategoryContentVariants::class]);

        $this->provider = new ProductsContentVariantProvider();
    }

    public function testItReturnsProperProductIds()
    {
        /** @var Product $testProduct1 */
        $testProduct1 = $this->getReference(LoadProductData::PRODUCT_1);
        /** @var Product $testProduct2 */
        $testProduct2 = $this->getReference(LoadProductData::PRODUCT_2);

        /** @var TestContentVariant $testContentVariant1 */
        $testContentVariant1 = $this->getReference('test_category_variant.1');
        /** @var TestContentVariant $testContentVariant2 */
        $testContentVariant2 = $this->getReference('test_category_variant.2');

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

        $this->assertEquals($testContentVariant1->getId(), $result[0]['variant_id']);
        $this->assertEquals($testProduct1->getId(), $result[0]['categoryProductId']);

        $this->assertEquals($testContentVariant2->getId(), $result[1]['variant_id']);
        $this->assertEquals($testProduct2->getId(), $result[1]['categoryProductId']);

        $this->assertNull($result[2]['categoryProductId']);
        $this->assertNull($result[3]['categoryProductId']);
        $this->assertNull($result[4]['categoryProductId']);
        $this->assertNull($result[5]['categoryProductId']);
    }
}
