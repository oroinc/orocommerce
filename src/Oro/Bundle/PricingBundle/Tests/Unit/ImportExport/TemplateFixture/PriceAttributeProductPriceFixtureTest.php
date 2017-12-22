<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\ImportExport\TemplateFixture;

use Oro\Bundle\ImportExportBundle\TemplateFixture\TemplateEntityRegistry;
use Oro\Bundle\ImportExportBundle\TemplateFixture\TemplateManager;
use Oro\Bundle\PricingBundle\Entity\PriceAttributeProductPrice;
use Oro\Bundle\PricingBundle\ImportExport\TemplateFixture\PriceAttributeProductPriceFixture;
use PHPUnit\Framework\TestCase;

class PriceAttributeProductPriceFixtureTest extends TestCase
{
    public function testDataIsCreatedAndFilled()
    {
        $registry = new TemplateEntityRegistry();
        $manager = new TemplateManager($registry);
        $fixture = new PriceAttributeProductPriceFixture();

        $fixture->setTemplateManager($manager);

        /** @var PriceAttributeProductPrice $attributePrice */
        $attributePrice = $fixture->getData()->current();

        $fixture->fillEntityData('', $attributePrice);

        static::assertSame(
            PriceAttributeProductPriceFixture::PRODUCT_SKU,
            $attributePrice->getProduct()->getSku()
        );
        static::assertSame(
            PriceAttributeProductPriceFixture::PRICE_ATTRIBUTE,
            $attributePrice->getPriceList()->getName()
        );
        static::assertSame(
            PriceAttributeProductPriceFixture::UNIT_CODE,
            $attributePrice->getProductUnit()->getCode()
        );

        $price = $attributePrice->getPrice();

        static::assertSame(
            PriceAttributeProductPriceFixture::CURRENCY,
            $price->getCurrency()
        );
        static::assertSame(
            PriceAttributeProductPriceFixture::PRICE,
            $price->getValue()
        );
    }
}
