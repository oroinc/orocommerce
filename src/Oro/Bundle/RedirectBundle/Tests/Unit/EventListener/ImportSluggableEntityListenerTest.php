<?php

namespace Oro\Bundle\RedirectBundle\Tests\Unit\EventListener;

use Oro\Bundle\ImportExportBundle\Context\Context;
use Oro\Bundle\ImportExportBundle\Event\StrategyEvent;
use Oro\Bundle\ImportExportBundle\Strategy\StrategyInterface;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\RedirectBundle\Entity\SluggableInterface;
use Oro\Bundle\RedirectBundle\EventListener\ImportSluggableEntityListener;
use Oro\Bundle\RedirectBundle\Helper\SlugifyEntityHelper;
use Oro\Bundle\RedirectBundle\Tests\Unit\Entity\SluggableEntityStub;
use Oro\Bundle\TestFrameworkBundle\Entity\TestActivity;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ImportSluggableEntityListenerTest extends TestCase
{
    private ImportSluggableEntityListener $listener;

    private SlugifyEntityHelper|MockObject $slugifyEntityHelper;

    protected function setUp(): void
    {
        $this->slugifyEntityHelper = $this->createMock(SlugifyEntityHelper::class);
        $this->listener = new ImportSluggableEntityListener($this->slugifyEntityHelper);
    }

    public function testOnProcessAfter(): void
    {
        $entity = new SluggableEntityStub();
        $context = new Context([]);
        $event = new StrategyEvent($this->createMock(StrategyInterface::class), $entity, $context);

        $this->assertNull($entity->getUpdatedAt());
        $this->listener->onProcessAfter($event);
        $this->assertNotNull($entity->getUpdatedAt());
    }

    public function testSluggableEntityWithoutItemData(): void
    {
        $entity = new SluggableEntityStub();
        $context = new Context([]);
        $event = new StrategyEvent($this->createMock(StrategyInterface::class), $entity, $context);

        $this->slugifyEntityHelper
            ->expects($this->once())
            ->method('fill');

        $this->listener->onProcessBefore($event);

        $this->assertNull($context->getValue('itemData'));
    }

    public function testSluggableEntityWithoutSlugPrototypes(): void
    {
        $entity = new SluggableEntityStub();
        $context = new Context([]);
        $context->setValue('itemData', []);
        $event = new StrategyEvent($this->createMock(StrategyInterface::class), $entity, $context);

        $this->slugifyEntityHelper
            ->expects($this->once())
            ->method('fill')
            ->willReturnCallback(
                function (SluggableInterface $sluggable) {
                    $sluggable->addSlugPrototype((new LocalizedFallbackValue())->setString('test'));

                    return true;
                }
            );

        $this->listener->onProcessBefore($event);

        $this->assertIsArray($context->getValue('itemData'));
    }

    public function testSluggableEntityWithOnlyEmptySlugPrototypes(): void
    {
        $entity = new SluggableEntityStub();
        $context = new Context([]);
        $context->setValue('itemData', ['slugPrototypes' => []]);
        $event = new StrategyEvent($this->createMock(StrategyInterface::class), $entity, $context);

        $this->slugifyEntityHelper
            ->expects($this->once())
            ->method('fill')
            ->willReturnCallback(
                function (SluggableInterface $sluggable) {
                    $sluggable->addSlugPrototype((new LocalizedFallbackValue())->setString('test'));

                    return true;
                }
            );
        $this->slugifyEntityHelper->expects(self::once())
            ->method('getSourceFieldName')
            ->with(SluggableEntityStub::class)
            ->willReturn('sourceField');

        $this->listener->onProcessBefore($event);

        $this->assertArrayHasKey('slugPrototypes', $context->getValue('itemData'));
        $this->assertEmpty($context->getValue('itemData')['slugPrototypes']);
    }

    public function testSluggableEntityWithSourceFieldItemDataEmptySlugPrototypes(): void
    {
        $entity = new SluggableEntityStub();
        $context = new Context([]);
        $context->setValue('itemData', [
            'sourceField' => ['default' => ['string' => 'test', 'fallback' => null]],
            'slugPrototypes' => []
        ]);
        $event = new StrategyEvent($this->createMock(StrategyInterface::class), $entity, $context);

        $this->slugifyEntityHelper
            ->expects($this->once())
            ->method('fill')
            ->willReturnCallback(
                function (SluggableInterface $sluggable) {
                    $sluggable->addSlugPrototype((new LocalizedFallbackValue())->setString('test'));

                    return true;
                }
            );
        $this->slugifyEntityHelper->expects(self::once())
            ->method('getSourceFieldName')
            ->with(SluggableEntityStub::class)
            ->willReturn('sourceField');

        $this->listener->onProcessBefore($event);

        $this->assertArrayHasKey('slugPrototypes', $context->getValue('itemData'));
        $this->assertEquals(
            ['default' => ['string' => 'test', 'fallback' => null]],
            $context->getValue('itemData')['slugPrototypes']
        );
    }

    public function testSluggableEntity(): void
    {
        $entity = new SluggableEntityStub();
        $basicLocalizedData = [
            'default' => ['string' => 'file'],
            'en' => ['fallback' => 'system'],
            'en_US' => [],
            'fr' => ['string' => 'file', 'fallback' => 'parent_localization'],
            'es' => ['string' => '', 'fallback' => ''],
            'de' => ['string' => null, 'fallback' => null],
            'xx1' => ['string' => '', 'fallback' => null],
            'xx2' => ['string' => null, 'fallback' => ''],
            'xx3' => ['string' => null],
            'xx4' => ['string' => ''],
            'xx5' => ['fallback' => null],
            'xx6' => ['fallback' => ''],
        ];
        $context = new Context([]);
        $context->setValue(
            'itemData',
            [
                'sourceField' => $basicLocalizedData + ['gb' => ['string' => 'testgb', 'fallback' => '']],
                'slugPrototypes' => $basicLocalizedData,
            ]
        );
        $event = new StrategyEvent($this->createMock(StrategyInterface::class), $entity, $context);

        $this->slugifyEntityHelper
            ->expects($this->once())
            ->method('fill')
            ->willReturnCallback(
                function (SluggableInterface $sluggable) {
                    $sluggable->addSlugPrototype((new LocalizedFallbackValue())->setString('test'));
                    $sluggable->addSlugPrototype(
                        (new LocalizedFallbackValue())
                            ->setString('testmx')
                            ->setLocalization((new Localization())->setName('mx'))
                    );
                    $sluggable->addSlugPrototype(
                        (new LocalizedFallbackValue())
                            ->setString('testgb')
                            ->setLocalization((new Localization())->setName('gb'))
                    );

                    return true;
                }
            );
        $this->slugifyEntityHelper->expects(self::once())
            ->method('getSourceFieldName')
            ->with(SluggableEntityStub::class)
            ->willReturn('sourceField');

        $this->listener->onProcessBefore($event);

        $this->assertArrayHasKey('slugPrototypes', $context->getValue('itemData'));
        $this->assertEquals(
            $basicLocalizedData + ['gb' => ['string' => 'testgb', 'fallback' => '']],
            $context->getValue('itemData')['slugPrototypes']
        );
    }

    public function testNonSluggableEntity(): void
    {
        $entity = new TestActivity();
        $event = $this->createMock(StrategyEvent::class);
        $event
            ->expects($this->once())
            ->method('getEntity')
            ->willReturn($entity);

        $this->slugifyEntityHelper
            ->expects($this->never())
            ->method('fill');

        $this->listener->onProcessBefore($event);
    }
}
