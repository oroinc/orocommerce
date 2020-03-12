<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\EventListener;

use Oro\Bundle\EntityConfigBundle\Generator\SlugGenerator;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\ProductBundle\EventListener\EmptySlugProductStrategyEventListener;
use Oro\Bundle\ProductBundle\ImportExport\Event\ProductStrategyEvent;
use Oro\Bundle\ProductBundle\Tests\Unit\Stub\ProductStub;
use Oro\Component\Testing\Unit\EntityTrait;

class EmptySlugProductStrategyEventListenerTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var EmptySlugProductStrategyEventListener */
    private $listener;

    /** @var SlugGenerator|\PHPUnit\Framework\MockObject\MockObject */
    private $slugGenerator;

    protected function setUp()
    {
        $this->slugGenerator = $this->createMock(SlugGenerator::class);

        $this->listener = new EmptySlugProductStrategyEventListener($this->slugGenerator);
    }

    public function testOnProcessAfterSlugPrototypesSet()
    {
        $product = new ProductStub();
        $product->setNames([(new LocalizedFallbackValue())->setString('Product 3')]);
        $product->addSlugPrototype((new LocalizedFallbackValue())->setString('custom-slug'));

        $this->slugGenerator->expects(self::never())
            ->method('slugify');

        $event = new ProductStrategyEvent($product, []);

        $this->listener->onProcessAfter($event);
        $updatedProduct = $event->getProduct();
        $slugPrototypes = $updatedProduct->getSlugPrototypes();

        self::assertCount(1, $slugPrototypes);
        self::assertEquals('custom-slug', $slugPrototypes->offsetGet(0)->getString());
    }

    public function testOnProcessAfterSlugPrototypesNotSet()
    {
        $product = new ProductStub();
        $localization = (new Localization())->setName('Ukrainian');

        $product->setNames([
            (new LocalizedFallbackValue())
                ->setString('Product 3'),
            (new LocalizedFallbackValue())
                ->setString('Product 3 Ukrainian')
                ->setLocalization($localization),
        ]);

        $this->slugGenerator->expects(self::exactly(2))
            ->method('slugify')
            ->withConsecutive(
                ['Product 3'],
                ['Product 3 Ukrainian']
            )
            ->willReturnOnConsecutiveCalls(
                'product-3',
                'product-3-ukrainian'
            );

        $event = new ProductStrategyEvent($product, []);

        $this->listener->onProcessAfter($event);
        $updatedProduct = $event->getProduct();
        $slugPrototypes = $updatedProduct->getSlugPrototypes();

        self::assertCount(2, $slugPrototypes);
        self::assertEquals('product-3', $slugPrototypes->offsetGet(0)->getString());
        self::assertEquals('Product 3', $updatedProduct->getNames()->offsetGet(0)->getString());
        self::assertEquals('product-3-ukrainian', $slugPrototypes->offsetGet(1)->getString());
        self::assertEquals('Product 3 Ukrainian', $updatedProduct->getNames()->offsetGet(1)->getString());
    }

    public function testOnProcessAfterSlugPrototypesNotSetDefault()
    {
        $product = new ProductStub();
        $localization = (new Localization())->setName('Ukrainian');

        $product->setNames([
            (new LocalizedFallbackValue())
                ->setString('Product 3'),
            (new LocalizedFallbackValue())
                ->setString('Product 3 Ukrainian')
                ->setLocalization($localization),
        ]);

        $product->addSlugPrototype(
            (new LocalizedFallbackValue())
                ->setFallback('system')
                ->setLocalization($localization)
        );

        $this->slugGenerator->expects($this->once())
            ->method('slugify')
            ->with('Product 3')
            ->willReturn('product-3');

        $event = new ProductStrategyEvent($product, []);

        $this->listener->onProcessAfter($event);
        $updatedProduct = $event->getProduct();
        $slugPrototypes = $updatedProduct->getSlugPrototypes();

        self::assertCount(2, $slugPrototypes);
        self::assertEquals(null, $slugPrototypes->offsetGet(0)->getString());
        self::assertEquals($localization, $slugPrototypes->offsetGet(0)->getLocalization());
        self::assertEquals('product-3', $slugPrototypes->offsetGet(1)->getString());
        self::assertEquals(null, $slugPrototypes->offsetGet(1)->getLocalization());
    }
}
