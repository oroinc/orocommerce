<?php

namespace Oro\Bundle\CatalogBundle\Tests\Unit\EventListener;

use Oro\Bundle\CatalogBundle\Entity\CategoryTitle;
use Oro\Bundle\CatalogBundle\EventListener\EmptySlugCategoryStrategyEventListener;
use Oro\Bundle\CatalogBundle\ImportExport\Event\CategoryStrategyAfterProcessEntityEvent;
use Oro\Bundle\CatalogBundle\Tests\Unit\Stub\CategoryStub;
use Oro\Bundle\EntityConfigBundle\Generator\SlugGenerator;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;

class EmptySlugCategoryStrategyEventListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var SlugGenerator|\PHPUnit\Framework\MockObject\MockObject */
    private $slugGenerator;

    /** @var EmptySlugCategoryStrategyEventListener */
    private $listener;

    #[\Override]
    protected function setUp(): void
    {
        $this->slugGenerator = $this->createMock(SlugGenerator::class);

        $this->listener = new EmptySlugCategoryStrategyEventListener($this->slugGenerator);
    }

    public function testOnProcessAfterSlugPrototypesSet(): void
    {
        $category = new CategoryStub();
        $category->addTitle((new CategoryTitle())->setString('Category 3'));
        $category->addSlugPrototype((new LocalizedFallbackValue())->setString('custom-slug'));

        $this->slugGenerator->expects(self::never())
            ->method('slugify');

        $event = new CategoryStrategyAfterProcessEntityEvent($category, []);

        $this->listener->onProcessAfter($event);

        $slugPrototypes = $category->getSlugPrototypes();

        self::assertCount(1, $slugPrototypes);
        self::assertEquals('custom-slug', $slugPrototypes->offsetGet(0)->getString());
    }

    public function testOnProcessAfterSlugPrototypesNotSet(): void
    {
        $localization = (new Localization())->setName('Ukrainian');

        $category = new CategoryStub();
        $category->addTitle((new CategoryTitle())->setString('Category 3'))
            ->addTitle((new CategoryTitle())->setString('Category 3 Ukrainian')->setLocalization($localization));

        $this->slugGenerator->expects(self::exactly(2))
            ->method('slugify')
            ->withConsecutive(
                ['Category 3'],
                ['Category 3 Ukrainian']
            )
            ->willReturnOnConsecutiveCalls(
                'category-3',
                'category-3-ukrainian'
            );

        $event = new CategoryStrategyAfterProcessEntityEvent($category, []);

        $this->listener->onProcessAfter($event);

        $slugPrototypes = $category->getSlugPrototypes();

        self::assertCount(2, $slugPrototypes);
        self::assertEquals('category-3', $slugPrototypes->offsetGet(0)->getString());
        self::assertEquals('Category 3', $category->getTitles()->offsetGet(0)->getString());
        self::assertEquals('category-3-ukrainian', $slugPrototypes->offsetGet(1)->getString());
        self::assertEquals('Category 3 Ukrainian', $category->getTitles()->offsetGet(1)->getString());
    }

    public function testOnProcessAfterSlugPrototypesNotSetDefault()
    {
        $localization = (new Localization())->setName('Ukrainian');

        $category = new CategoryStub();
        $category->addTitle((new CategoryTitle())->setString('Category 3'))
            ->addTitle((new CategoryTitle())->setString('Category 3 Ukrainian')->setLocalization($localization));

        $category->addSlugPrototype(
            (new LocalizedFallbackValue())
                ->setFallback('system')
                ->setLocalization($localization)
        );

        $this->slugGenerator->expects(self::once())
            ->method('slugify')
            ->with('Category 3')
            ->willReturn('category-3');

        $event = new CategoryStrategyAfterProcessEntityEvent($category, []);

        $this->listener->onProcessAfter($event);
        $updatedProduct = $event->getCategory();
        $slugPrototypes = $updatedProduct->getSlugPrototypes();

        self::assertCount(2, $slugPrototypes);
        self::assertEquals(null, $slugPrototypes->offsetGet(0)->getString());
        self::assertEquals($localization, $slugPrototypes->offsetGet(0)->getLocalization());
        self::assertEquals('category-3', $slugPrototypes->offsetGet(1)->getString());
        self::assertEquals(null, $slugPrototypes->offsetGet(1)->getLocalization());
    }
}
