<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Unit\Cache\ResolvedData;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\WebCatalogBundle\Cache\ResolvedData\ResolvedContentVariant;

class ResolvedContentVariantTest extends \PHPUnit\Framework\TestCase
{
    public function testAccessors()
    {
        $localizedUrl = (new LocalizedFallbackValue())->setString('/test');
        $variant = (new ResolvedContentVariant())
            ->setData(['id' => 3, 'type' => 'test_type', 'test' => 1])
            ->addLocalizedUrl($localizedUrl);
        $variant->property = 'test';

        $this->assertEquals(3, $variant->getId());
        $this->assertEquals('test_type', $variant->getType());
        $this->assertEquals(
            ['id' => 3, 'type' => 'test_type', 'test' => 1, 'property' => 'test'],
            $variant->getData()
        );
        $this->assertEquals(new ArrayCollection([$localizedUrl]), $variant->getLocalizedUrls());
        $this->assertEquals('test', $variant->property);

        unset($variant->property);
        $this->assertNull($variant->property);
    }
}
