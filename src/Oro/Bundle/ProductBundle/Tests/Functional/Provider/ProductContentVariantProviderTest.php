<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\Provider;

use Doctrine\ORM\EntityRepository;
use Oro\Bundle\FrontendTestFrameworkBundle\Entity\TestContentVariant;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Provider\ProductContentVariantProvider;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductContentVariants;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class ProductContentVariantProviderTest extends WebTestCase
{
    private ProductContentVariantProvider $provider;

    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([LoadProductContentVariants::class]);

        $this->provider = new ProductContentVariantProvider();
    }

    public function testItReturnsProperProductIds()
    {
        /** @var Product $testProduct1 */
        $testProduct1 = $this->getReference('product-1');
        /** @var Product $testProduct2 */
        $testProduct2 = $this->getReference('product-2');

        /** @var TestContentVariant $testContentVariant1 */
        $testContentVariant1 = $this->getReference('test_product_variant.1');
        /** @var TestContentVariant $testContentVariant2 */
        $testContentVariant2 = $this->getReference('test_product_variant.2');

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
        /** @var array $result */
        $result = $qb->getQuery()->getScalarResult();

        $this->assertEquals($testContentVariant1->getId(), $result[0]['variant_id']);
        $this->assertEquals($testProduct1->getId(), $result[0]['productId']);

        $this->assertEquals($testContentVariant2->getId(), $result[1]['variant_id']);
        $this->assertEquals($testProduct2->getId(), $result[1]['productId']);

        $this->assertNull($result[2]['productId']);
        $this->assertNull($result[3]['productId']);
        $this->assertNull($result[4]['productId']);
        $this->assertNull($result[5]['productId']);
    }
}
