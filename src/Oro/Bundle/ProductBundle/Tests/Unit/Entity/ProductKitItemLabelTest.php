<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Entity;

use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\ProductBundle\Entity\ProductKitItem;
use Oro\Bundle\ProductBundle\Entity\ProductKitItemLabel;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;

class ProductKitItemLabelTest extends \PHPUnit\Framework\TestCase
{
    use EntityTestCaseTrait;

    public function testProperties(): void
    {
        self::assertPropertyAccessors(
            new ProductKitItemLabel(),
            [
                ['id', 123],
                ['fallback', 'test'],
                ['string', 'text string'],
                ['localization', new Localization()],
                ['kitItem', new ProductKitItem()],
            ]
        );
    }
}
