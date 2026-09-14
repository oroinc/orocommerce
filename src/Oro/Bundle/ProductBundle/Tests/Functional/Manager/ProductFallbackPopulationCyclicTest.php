<?php

declare(strict_types=1);

namespace Oro\Bundle\ProductBundle\Tests\Functional\Manager;

use Oro\Bundle\EntityBundle\Entity\EntityFieldFallbackValue;
use Oro\Bundle\EntityExtendBundle\PropertyAccess;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Manager\ProductFallbackUpdateManager;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductFallbackCyclicData;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductFallbackData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolationPerTest
 */
class ProductFallbackPopulationCyclicTest extends WebTestCase
{
    private ProductFallbackUpdateManager $manager;

    #[\Override]
    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([LoadProductFallbackCyclicData::class]);

        $this->manager = self::getContainer()->get('oro_product.manager.fallback_update');
    }

    public function testPopulationCompletesWhenFallbacksFormACycle(): void
    {
        /** @var Product $product */
        $product = $this->getReference(LoadProductFallbackData::PRODUCT_NESTED);

        // The statement reads and writes products and fallback values only and never walks the category tree,
        // so a cycle in that tree cannot make it loop. Resolving the value is not attempted on purpose:
        // EntityFallbackResolver recurses through such a cycle without a bound, before this change as well.
        self::assertSame(1, $this->manager->processChunk([$product->getId()]));

        $em = self::getContainer()->get('doctrine')->getManagerForClass(Product::class);
        $em->clear();
        $product = $em->find(Product::class, $product->getId());

        $propertyAccessor = PropertyAccess::createPropertyAccessor();
        foreach (LoadProductFallbackData::FALLBACK_FIELDS as $field) {
            self::assertInstanceOf(
                EntityFieldFallbackValue::class,
                $propertyAccessor->getValue($product, $field),
                $field
            );
        }
    }
}
