<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\EventListener;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CMSBundle\Entity\ContentTemplate;
use Oro\Bundle\CMSBundle\Entity\ContentWidget;
use Oro\Bundle\CMSBundle\EventListener\ContentWidgetLabelsFormEventListener;
use Oro\Bundle\FormBundle\Event\FormHandler\AfterFormProcessEvent;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormInterface;

class ContentWidgetLabelsFormEventListenerTest extends TestCase
{
    private ManagerRegistry|MockObject $registry;

    protected function setUp(): void
    {
        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->listener = new ContentWidgetLabelsFormEventListener($this->registry);
    }

    /**
     * @dataProvider onBeforeFlushNoApplicableDataProvider
     */
    public function testOnBeforeFlushNoApplicable(mixed $data): void
    {
        $args = new AfterFormProcessEvent($this->createMock(FormInterface::class), $data);
        $this->listener->onBeforeFlush($args);

        $this->registry->expects(self::never())
            ->method('getManagerForClass')
            ->with(LocalizedFallbackValue::class)
            ->willReturn($this->createMock(ObjectManager::class));
    }

    /**
     * @dataProvider onBeforeFlushDataProvider
     */
    public function testOnBeforeFlush(ContentWidget $contentWidget, array $settings, Collection $labels): void
    {
        $manager = $this->createMock(ObjectManager::class);
        $manager->expects(self::any())
            ->method('persist');

        $this->registry->expects(self::any())
            ->method('getManagerForClass')
            ->with(LocalizedFallbackValue::class)
            ->willReturn($manager);

        $args = new AfterFormProcessEvent($this->createMock(FormInterface::class), $contentWidget);
        $this->listener->onBeforeFlush($args);

        self::assertEquals($settings, $args->getData()->getSettings());
        self::assertEquals($labels, $args->getData()->getLabels());
    }

    public function onBeforeFlushNoApplicableDataProvider(): array
    {
        return [
            'empty data' => [
                'data' => null
            ],
            'no content widget' => [
                'data' => new ContentTemplate()
            ],
            'content widget without labels' => [
                'data' => new ContentWidget()
            ],
            'content widget with wrong labels type' => [
                'data' => (new ContentWidget())->setSettings(['labels' => 'English Label'])
            ],
        ];
    }

    public function onBeforeFlushDataProvider(): array
    {
        $contentWidget = new ContentWidget();

        return [
            'content widget with empty labels' => [
                'contentWidget' => $contentWidget->setSettings([
                    'autoplay' => false
                ]),
                'settings' => ['autoplay' => false],
                'labels' => $contentWidget->getLabels()
            ],
            'content widget with labels' => [
                'contentWidget' => $contentWidget->setSettings([
                    'autoplay' => false,
                    'labels' => new ArrayCollection([
                        (new LocalizedFallbackValue())->setString('Default Label'),
                        (new LocalizedFallbackValue())->setString('France Label'),
                    ])
                ]),
                'settings' => ['autoplay' => false],
                'labels' => $contentWidget->getSettings()['labels']
            ],
        ];
    }
}
