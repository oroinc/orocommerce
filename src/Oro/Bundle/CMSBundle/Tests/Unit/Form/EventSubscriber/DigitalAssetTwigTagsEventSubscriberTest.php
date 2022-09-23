<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\Form\EventSubscriber;

use Oro\Bundle\CMSBundle\Form\EventSubscriber\DigitalAssetTwigTagsEventSubscriber;
use Oro\Bundle\CMSBundle\Tools\DigitalAssetTwigTagsConverter;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormConfigBuilder;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;

class DigitalAssetTwigTagsEventSubscriberTest extends \PHPUnit\Framework\TestCase
{
    private DigitalAssetTwigTagsConverter|\PHPUnit\Framework\MockObject\MockObject $digitalAssetTwigTagsConverter;

    private DigitalAssetTwigTagsEventSubscriber $eventSubscriber;

    protected function setUp(): void
    {
        $this->digitalAssetTwigTagsConverter = $this->createMock(DigitalAssetTwigTagsConverter::class);
        $this->eventSubscriber = new DigitalAssetTwigTagsEventSubscriber($this->digitalAssetTwigTagsConverter);
    }

    public function testGetSubscribedEvents(): void
    {
        self::assertEquals(
            [
                FormEvents::PRE_SET_DATA => 'onPreSetData',
                FormEvents::PRE_SUBMIT => 'onPreSubmit',
            ],
            $this->eventSubscriber::getSubscribedEvents()
        );
    }

    public function testOnPreSetDataWhenNoData(): void
    {
        $formConfig = new FormConfigBuilder(
            'sample_form',
            \stdClass::class,
            $this->createMock(EventDispatcherInterface::class),
            []
        );
        $formEvent = new FormEvent(new Form($formConfig), null);

        $this->digitalAssetTwigTagsConverter
            ->expects(self::never())
            ->method(self::anything());

        self::assertNull($formEvent->getData());
        $this->eventSubscriber->onPreSetData($formEvent);
        self::assertNull($formEvent->getData());
    }

    /**
     * @dataProvider contextFromFormDataProvider
     */
    public function testOnPreSetData(FormInterface $form, array $expectedContext): void
    {
        $content = 'sample content';
        $formEvent = new FormEvent($form, $content);

        $this->digitalAssetTwigTagsConverter
            ->expects(self::once())
            ->method('convertToUrls')
            ->with($content, self::identicalTo($expectedContext))
            ->willReturnCallback(static fn (string $content) => $content . '[converted to urls]');

        $this->eventSubscriber->onPreSetData($formEvent);
        self::assertEquals($content . '[converted to urls]', $formEvent->getData());
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function contextFromFormDataProvider(): array
    {
        $formConfig = new FormConfigBuilder(
            'sample_form',
            \stdClass::class,
            $this->createMock(EventDispatcherInterface::class),
            []
        );
        $rootData = new class extends \stdClass {
            public function getId(): int
            {
                return 42;
            }
        };
        $rootFormConfig = new FormConfigBuilder(
            'root_form',
            get_class($rootData),
            $this->createMock(EventDispatcherInterface::class),
            []
        );
        $parentFormConfig = new FormConfigBuilder(
            'parent_form',
            null,
            $this->createMock(EventDispatcherInterface::class),
            []
        );
        $parentData = new class extends \stdClass {
            public function getId(): int
            {
                return 4242;
            }
        };
        $parentFormConfigWithDataClass = new FormConfigBuilder(
            'parent_form',
            get_class($parentData),
            $this->createMock(EventDispatcherInterface::class),
            []
        );

        return [
            'with parent form but without dataclass' => [
                'form' => (new Form($formConfig))
                    ->setParent(new Form($parentFormConfig)),
                'expectedContext' => [
                    'entityClass' => '',
                    'entityId' => null,
                    'fieldName' => 'sample_form',
                ],
            ],
            'with root and parent form but without dataclass' => [
                'form' => (new Form($formConfig))
                    ->setParent(
                        (new Form($parentFormConfig))
                            ->setParent(new Form($rootFormConfig))
                    ),
                'expectedContext' => [
                    'entityClass' => $rootFormConfig->getDataClass(),
                    'entityId' => null,
                    'fieldName' => 'sample_form',
                ],
            ],
            'with root form data and parent form but without dataclass' => [
                'form' => (new Form($formConfig))
                    ->setParent(
                        (new Form($parentFormConfig))
                            ->setParent((new Form($rootFormConfig))->setData($rootData))
                    ),
                'expectedContext' => [
                    'entityClass' => $rootFormConfig->getDataClass(),
                    'entityId' => 42,
                    'fieldName' => 'sample_form',
                ],
            ],
            'with parent form and dataclass' => [
                'form' => (new Form($formConfig))
                    ->setParent(new Form($parentFormConfigWithDataClass)),
                'expectedContext' => [
                    'entityClass' => $parentFormConfigWithDataClass->getDataClass(),
                    'entityId' => null,
                    'fieldName' => 'sample_form',
                ],
            ],
            'with parent form, dataclass and data' => [
                'form' => (new Form($formConfig))
                    ->setParent((new Form($parentFormConfigWithDataClass))->setData($parentData)),
                'expectedContext' => [
                    'entityClass' => $parentFormConfigWithDataClass->getDataClass(),
                    'entityId' => $parentData->getId(),
                    'fieldName' => 'sample_form',
                ],
            ],
            'without root form data' => [
                'form' => (new Form($formConfig))
                    ->setParent(new Form($rootFormConfig)),
                'expectedContext' => [
                    'entityClass' => $rootFormConfig->getDataClass(),
                    'entityId' => null,
                    'fieldName' => 'sample_form',
                ],
            ],
            'with root form data' => [
                'form' => (new Form($formConfig))
                    ->setParent((new Form($rootFormConfig))->setData($rootData)),
                'expectedContext' => [
                    'entityClass' => $rootFormConfig->getDataClass(),
                    'entityId' => 42,
                    'fieldName' => 'sample_form',
                ],
            ],
        ];
    }

    public function testOnPreSubmitWhenNoData(): void
    {
        $formConfig = new FormConfigBuilder(
            'sample_form',
            \stdClass::class,
            $this->createMock(EventDispatcherInterface::class),
            []
        );
        $formEvent = new FormEvent(new Form($formConfig), null);

        $this->digitalAssetTwigTagsConverter
            ->expects(self::never())
            ->method(self::anything());

        self::assertNull($formEvent->getData());
        $this->eventSubscriber->onPreSubmit($formEvent);
        self::assertNull($formEvent->getData());
    }

    /**
     * @dataProvider contextFromFormDataProvider
     */
    public function testOnPreSubmit(FormInterface $form, array $expectedContext): void
    {
        $content = 'sample content';
        $formEvent = new FormEvent($form, $content);

        $this->digitalAssetTwigTagsConverter
            ->expects(self::once())
            ->method('convertToTwigTags')
            ->with($content, self::identicalTo($expectedContext))
            ->willReturnCallback(static fn (string $content) => $content . '[converted to TWIG tags]');

        $this->eventSubscriber->onPreSubmit($formEvent);
        self::assertEquals($content . '[converted to TWIG tags]', $formEvent->getData());
    }
}
