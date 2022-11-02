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

class ImportSluggableEntityListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var ImportSluggableEntityListener */
    private $listener;

    /** @var SlugifyEntityHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $slugifyEntityHelper;

    protected function setUp(): void
    {
        $this->slugifyEntityHelper = $this->createMock(SlugifyEntityHelper::class);
        $this->listener = new ImportSluggableEntityListener($this->slugifyEntityHelper);
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

    public function testSluggableEntityWithEmptySlugPrototypes(): void
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
        $context = new Context([]);
        $context->setValue(
            'itemData',
            [
                'slugPrototypes' => [
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
                ],
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

        $this->listener->onProcessBefore($event);

        $this->assertArrayHasKey('slugPrototypes', $context->getValue('itemData'));
        $this->assertEquals(
            [
                'default' => ['string' => 'file'],
                'en' => ['fallback' => 'system'],
                'en_US' => [],
                'fr' => ['string' => 'file', 'fallback' => 'parent_localization'],
                'es' => ['string' => '', 'fallback' => ''],
                'de' => ['string' => null, 'fallback' => null],
                'mx' => ['string' => 'testmx', 'fallback' => ''],
                'gb' => ['string' => 'testgb', 'fallback' => ''],
                'xx1' => ['string' => '', 'fallback' => null],
                'xx2' => ['string' => null, 'fallback' => ''],
                'xx3' => ['string' => null],
                'xx4' => ['string' => ''],
                'xx5' => ['fallback' => null],
                'xx6' => ['fallback' => ''],
            ],
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
