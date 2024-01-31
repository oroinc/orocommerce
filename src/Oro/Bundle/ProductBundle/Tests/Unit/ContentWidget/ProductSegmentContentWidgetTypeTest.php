<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\ContentWidget;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CMSBundle\Entity\ContentWidget;
use Oro\Bundle\FormBundle\Form\Extension\DataBlockExtension;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\LocaleBundle\Form\Type\LocalizedFallbackValueCollectionType;
use Oro\Bundle\LocaleBundle\Tests\Unit\Form\Type\Stub\LocalizedFallbackValueCollectionTypeStub;
use Oro\Bundle\ProductBundle\ContentWidget\ProductSegmentContentWidgetType;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Form\Type\ProductSegmentContentWidgetSettingsType;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Bundle\SegmentBundle\Entity\Repository\SegmentRepository;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use Oro\Bundle\SegmentBundle\Form\Type\SegmentChoiceType;
use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Twig\Environment;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class ProductSegmentContentWidgetTypeTest extends FormIntegrationTestCase
{
    use EntityTrait;

    /** @var SegmentRepository|\PHPUnit\Framework\MockObject\MockObject */
    private $repository;

    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $registry;

    /** @var ProductSegmentContentWidgetType */
    private $contentWidgetType;

    /** @var AclHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $aclHelper;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(SegmentRepository::class);
        $this->aclHelper = $this->createMock(AclHelper::class);
        $this->registry = $this->createMock(ManagerRegistry::class);

        $this->registry->expects($this->any())
            ->method('getRepository')
            ->with(Segment::class)
            ->willReturn($this->repository);

        $this->contentWidgetType = new ProductSegmentContentWidgetType($this->registry);

        parent::setUp();
    }

    public function testGetName(): void
    {
        $this->assertEquals('product_segment', $this->contentWidgetType::getName());
    }

    public function testGetLabel(): void
    {
        $this->assertEquals(
            'oro.product.content_widget_type.product_segment.label',
            $this->contentWidgetType->getLabel()
        );
    }

    public function testIsInline(): void
    {
        $this->assertFalse($this->contentWidgetType->isInline());
    }

    public function testGetWidgetData(): void
    {
        $label = new LocalizedFallbackValue();
        $label->setString('Label');

        $contentWidget = new ContentWidget();
        $contentWidget->setName('test_name');
        $contentWidget->addLabel($label);
        $contentWidget->setSettings(['segment' => 42]);

        $segment = $this->getEntity(Segment::class, ['id' => 42]);

        $this->repository->expects($this->once())
            ->method('find')
            ->with(42)
            ->willReturn($segment);

        $this->assertSame(
            [
                'slider_options' => ['data' => [
                    'slidesToShow' => 5,
                    'responsive' => [
                        ['breakpoint' => 1367, 'settings' => ['slidesToShow' => 4, 'arrows' => true]],
                        ['breakpoint' => 1281, 'settings' => ['slidesToShow' => 3, 'arrows' => true]],
                        ['breakpoint' => 769, 'settings' => ['slidesToShow' => 2, 'arrows' => false, 'dots' => true]],
                        ['breakpoint' => 641, 'settings' => ['slidesToShow' => 1, 'arrows' => false, 'dots' => true]],
                    ],
                ]],
                'product_segment' => $segment,
                'instanceNumber' => 0,
                'contentWidgetName' => 'test_name',
                'defaultLabel' => $contentWidget->getDefaultLabel(),
                'labels' => $contentWidget->getLabels(),
            ],
            $this->contentWidgetType->getWidgetData($contentWidget)
        );
    }

    public function testGetWidgetDataNoLabel(): void
    {
        $contentWidget = new ContentWidget();
        $contentWidget->setName('test_name');
        $contentWidget->setSettings(['segment' => 42]);

        $segment = $this->getEntity(Segment::class, ['id' => 42]);

        $this->repository->expects($this->once())
            ->method('find')
            ->with(42)
            ->willReturn($segment);

        $this->assertSame(
            [
                'slider_options' => ['data' => [
                    'slidesToShow' => 5,
                    'responsive' => [
                        ['breakpoint' => 1367, 'settings' => ['slidesToShow' => 4, 'arrows' => true]],
                        ['breakpoint' => 1281, 'settings' => ['slidesToShow' => 3, 'arrows' => true]],
                        ['breakpoint' => 769, 'settings' => ['slidesToShow' => 2, 'arrows' => false, 'dots' => true]],
                        ['breakpoint' => 641, 'settings' => ['slidesToShow' => 1, 'arrows' => false, 'dots' => true]],
                    ],
                ]],
                'product_segment' => $segment,
                'instanceNumber' => 0,
                'contentWidgetName' => 'test_name',
                'defaultLabel' => $contentWidget->getDefaultLabel(),
                'labels' => $contentWidget->getLabels(),
            ],
            $this->contentWidgetType->getWidgetData($contentWidget)
        );
    }

    public function testGetWidgetDataNoEntity(): void
    {
        $contentWidget = new ContentWidget();
        $contentWidget->setName('test_name');
        $contentWidget->setSettings(['segment' => 42]);

        $this->repository->expects($this->once())
            ->method('find')
            ->with(42)
            ->willReturn(null);

        $this->assertSame(
            [
                'slider_options' => ['data' => [
                    'slidesToShow' => 5,
                    'responsive' => [
                        ['breakpoint' => 1367, 'settings' => ['slidesToShow' => 4, 'arrows' => true]],
                        ['breakpoint' => 1281, 'settings' => ['slidesToShow' => 3, 'arrows' => true]],
                        ['breakpoint' => 769, 'settings' => ['slidesToShow' => 2, 'arrows' => false, 'dots' => true]],
                        ['breakpoint' => 641, 'settings' => ['slidesToShow' => 1, 'arrows' => false, 'dots' => true]],
                    ],
                ]],
                'product_segment' => null,
                'instanceNumber' => 0,
                'contentWidgetName' => 'test_name',
                'defaultLabel' => $contentWidget->getDefaultLabel(),
                'labels' => $contentWidget->getLabels(),
            ],
            $this->contentWidgetType->getWidgetData($contentWidget)
        );
    }

    public function testGetWidgetDataNoEntityId(): void
    {
        $contentWidget = new ContentWidget();
        $contentWidget->setName('test_name');

        $this->repository->expects($this->never())
            ->method('find');

        $this->assertSame(
            [
                'slider_options' => ['data' => [
                    'slidesToShow' => 5,
                    'responsive' => [
                        ['breakpoint' => 1367, 'settings' => ['slidesToShow' => 4, 'arrows' => true]],
                        ['breakpoint' => 1281, 'settings' => ['slidesToShow' => 3, 'arrows' => true]],
                        ['breakpoint' => 769, 'settings' => ['slidesToShow' => 2, 'arrows' => false,'dots' => true]],
                        ['breakpoint' => 641, 'settings' => ['slidesToShow' => 1, 'arrows' => false, 'dots' => true]],
                    ],
                ]],
                'product_segment' => null,
                'instanceNumber' => 0,
                'contentWidgetName' => 'test_name',
                'defaultLabel' => $contentWidget->getDefaultLabel(),
                'labels' => $contentWidget->getLabels(),
            ],
            $this->contentWidgetType->getWidgetData($contentWidget)
        );
    }

    public function testGetSettingsForm(): void
    {
        $this->repository->expects($this->any())
            ->method('findByEntity')
            ->with($this->aclHelper, Product::class);

        $manager = $this->createMock(ObjectManager::class);
        $manager->expects($this->any())
            ->method('getRepository')
            ->with(Segment::class)
            ->willReturn($this->repository);

        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->with(Segment::class)
            ->willReturn($manager);

        $form = $this->contentWidgetType->getSettingsForm(new ContentWidget(), $this->factory);

        $this->assertInstanceOf(
            ProductSegmentContentWidgetSettingsType::class,
            $form->getConfig()->getType()->getInnerType()
        );
    }

    public function testGetBackOfficeViewSubBlocks(): void
    {
        $contentWidget = new ContentWidget();
        $contentWidget->setName('test_name');
        $contentWidget->setSettings(['segment' => 42]);

        $segment = $this->getEntity(Segment::class, ['id' => 42]);

        $this->repository->expects($this->any())
            ->method('find')
            ->with(42)
            ->willReturn($segment);

        $twig = $this->createMock(Environment::class);
        $twig->expects($this->once())
            ->method('render')
            ->with(
                '@OroProduct/ProductSegmentContentWidget/options.html.twig',
                [
                    'instanceNumber' => 0,
                    'contentWidgetName' => 'test_name',
                    'defaultLabel' => $contentWidget->getDefaultLabel(),
                    'labels' => $contentWidget->getLabels(),
                    'product_segment' => $segment,
                    'slider_options' => ['data' => [
                        'slidesToShow' => 5,
                        'responsive' => [
                            ['breakpoint' => 1367, 'settings' => ['slidesToShow' => 4, 'arrows' => true]],
                            ['breakpoint' => 1281, 'settings' => ['slidesToShow' => 3, 'arrows' => true]],
                            [
                                'breakpoint' => 769,
                                'settings' => [
                                    'slidesToShow' => 2,
                                    'arrows' => false,
                                    'dots' => true,
                                ],
                            ],
                            [
                                'breakpoint' => 641,
                                'settings' => [
                                    'slidesToShow' => 1,
                                    'arrows' => false,
                                    'dots' => true,
                                ],
                            ],
                        ],
                    ]],
                ],
            )
            ->willReturn('rendered settings template');

        $this->assertEquals(
            [
                [
                    'title' => 'oro.product.sections.options',
                    'subblocks' => [['data' => ['rendered settings template']]]
                ],
            ],
            $this->contentWidgetType->getBackOfficeViewSubBlocks($contentWidget, $twig)
        );
    }

    public function testGetBackOfficeWithLabelsViewSubBlocks(): void
    {
        $label = new LocalizedFallbackValue();
        $label->setString('Label');

        $contentWidget = new ContentWidget();
        $contentWidget->setName('test_name');
        $contentWidget->setSettings(['segment' => 42]);
        $contentWidget->addLabel($label);

        $segment = $this->getEntity(Segment::class, ['id' => 42]);

        $this->repository->expects($this->any())
            ->method('find')
            ->with(42)
            ->willReturn($segment);

        $twig = $this->createMock(Environment::class);
        $twig->expects($this->once())
            ->method('render')
            ->with(
                '@OroProduct/ProductSegmentContentWidget/options.html.twig',
                [
                    'instanceNumber' => 0,
                    'contentWidgetName' => 'test_name',
                    'defaultLabel' => $contentWidget->getDefaultLabel(),
                    'labels' => $contentWidget->getLabels(),
                    'product_segment' => $segment,
                    'slider_options' => ['data' => [
                        'slidesToShow' => 5,
                        'responsive' => [
                            ['breakpoint' => 1367, 'settings' => ['slidesToShow' => 4, 'arrows' => true]],
                            ['breakpoint' => 1281, 'settings' => ['slidesToShow' => 3, 'arrows' => true]],
                            [
                                'breakpoint' => 769,
                                'settings' => [
                                    'slidesToShow' => 2,
                                    'arrows' => false,
                                    'dots' => true,
                                ],
                            ],
                            [
                                'breakpoint' => 641,
                                'settings' => [
                                    'slidesToShow' => 1,
                                    'arrows' => false,
                                    'dots' => true,
                                ],
                            ],
                        ],
                    ]],
                ],
            )
            ->willReturn('rendered settings template');

        $this->assertEquals(
            [
                [
                    'title' => 'oro.product.sections.options',
                    'subblocks' => [['data' => ['rendered settings template']]]
                ],
            ],
            $this->contentWidgetType->getBackOfficeViewSubBlocks($contentWidget, $twig)
        );
    }

    public function testGetDefaultTemplate(): void
    {
        $contentWidget = new ContentWidget();

        $twig = $this->createMock(Environment::class);

        $this->assertEquals('', $this->contentWidgetType->getDefaultTemplate($contentWidget, $twig));
    }

    protected function getExtensions(): array
    {
        return [
            new PreloadedExtension(
                [
                    LocalizedFallbackValueCollectionType::class => new LocalizedFallbackValueCollectionTypeStub(),
                    ProductSegmentContentWidgetSettingsType::class => new ProductSegmentContentWidgetSettingsType(),
                    SegmentChoiceType::class => new SegmentChoiceType($this->registry, $this->aclHelper),
                ],
                []
            ),
            $this->getValidatorExtension(true)
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function getTypeExtensions(): array
    {
        return array_merge(
            parent::getExtensions(),
            [
                new DataBlockExtension(),
            ]
        );
    }
}
