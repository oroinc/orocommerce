<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\PricingBundle\Entity\PriceAttributePriceList;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceAttributeProductPrices;
use Oro\Bundle\ProductBundle\Entity\Product;

/**
 * @dbIsolation
 */
class PriceAttributeProductPriceRepositoryTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->loadFixtures([LoadPriceAttributeProductPrices::class]);
    }

    public function testFindByPriceAttributeProductPriceIdsAndProductIds()
    {
        /** @var Product $product1 */
        $product1 = $this->getReference('product.1');
        /** @var Product $product2 */
        $product2 = $this->getReference('product.2');
        /** @var PriceAttributePriceList $priceAttributePriceList1 */
        $priceAttributePriceList1 = $this->getReference('price_attribute_price_list_1');
        /** @var PriceAttributePriceList $priceAttributePriceList2 */
        $priceAttributePriceList2 = $this->getReference('price_attribute_price_list_2');
        $repo = $this->getContainer()
            ->get('doctrine')
            ->getRepository('OroPricingBundle:PriceAttributeProductPrice');
        $result = $repo->findByPriceAttributeProductPriceIdsAndProductIds(
            [$priceAttributePriceList1->getId(), $priceAttributePriceList2->getId()],
            [$product1->getId(), $product2->getId()]
        );
        $this->assertCount(11, $result);
        $result = $repo->findByPriceAttributeProductPriceIdsAndProductIds(
            [$priceAttributePriceList2->getId()],
            [$product1->getId(), $product2->getId()]
        );
        $this->assertCount(4, $result);
    }
}
