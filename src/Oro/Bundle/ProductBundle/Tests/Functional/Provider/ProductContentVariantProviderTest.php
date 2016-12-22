<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\Provider;

use Doctrine\ORM\QueryBuilder;

use Oro\Bundle\FrontendTestFrameworkBundle\Entity\TestContentVariant;
use Oro\Bundle\ProductBundle\Provider\ProductContentVariantProvider;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadTestContentVariant;
use Oro\Bundle\ProductBundle\Tests\Unit\Entity\Stub\Product;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolation
 */
class ProductContentVariantProviderTest extends WebTestCase
{
    /** @var ProductContentVariantProvider */
    private $provider;

    /** @var QueryBuilder */
    private $qb;

    protected function setUp()
    {
        $this->initClient();
        $this->loadFixtures([LoadTestContentVariant::class]);

        $this->provider = new ProductContentVariantProvider();
        $this->qb = $this->getContainer()
            ->get('doctrine')
            ->getRepository(TestContentVariant::class)
            ->createQueryBuilder('variant');

        $this->qb
            ->orderBy('variant.id', 'ASC');
    }

    public function testItReturnsProperProductIds()
    {
        /** @var Product $testProduct1 */
        $testProduct1 = $this->getReference('product.1');
        /** @var Product $testProduct2 */
        $testProduct2 = $this->getReference('product.2');

        /** @var TestContentVariant $testContentVariant1 */
        $testContentVariant1 = $this->getReference('test_content_variant.1');
        /** @var TestContentVariant $testContentVariant2 */
        $testContentVariant2 = $this->getReference('test_content_variant.2');

        $this->provider->modifyNodeQueryBuilderByEntities(
            $this->qb,
            null,
            [$testProduct1->getId(), $testProduct2->getId()]
        );
        $result = $this->qb->getQuery()->getScalarResult();

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
