<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\ContentWidget;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CMSBundle\Entity\ContentWidget;
use Oro\Bundle\FormBundle\Form\Extension\DataBlockExtension;
use Oro\Bundle\ProductBundle\ContentWidget\ProductSegmentContentWidgetType;
use Oro\Bundle\ProductBundle\Form\Type\ProductSegmentContentWidgetSettingsType;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Bundle\SegmentBundle\Entity\Repository\SegmentRepository;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use Oro\Bundle\SegmentBundle\Form\Type\SegmentChoiceType;
use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Twig\Environment;

class ProductSegmentContentWidgetTypeTest extends FormIntegrationTestCase
{
    use EntityTrait;

    /** @var SegmentRepository|\PHPUnit\Framework\MockObject\MockObject */
    private $repository;

    /** @var ObjectManager|\PHPUnit\Framework\MockObject\MockObject */
    private $manager;

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

        $this->manager = $this->createMock(ObjectManager::class);
        $this->manager->expects($this->any())
            ->method('getRepository')
            ->with(Segment::class)
            ->willReturn($this->repository);

        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->registry->expects($this->any())
            ->method('getManagerForClass')
            ->with(Segment::class)
            ->willReturn($this->manager);

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
        $contentWidget = new ContentWidget();
        $contentWidget->setName('test_name');
        $contentWidget->setSettings(['segment' => 42]);

        $segment = $this->getEntity(Segment::class, ['id' => 42]);

        $this->repository->expects($this->once())
            ->method('find')
            ->with(42)
            ->willReturn($segment);

        $this->assertSame(
            ['instanceNumber' => 0, 'product_segment' => $segment],
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
            ['instanceNumber' => 0, 'product_segment' => null],
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
            ['instanceNumber' => 0, 'product_segment' => null],
            $this->contentWidgetType->getWidgetData($contentWidget)
        );
    }

    public function testGetSettingsForm(): void
    {
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
                ['instanceNumber' => 0, 'product_segment' => $segment]
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
                    ProductSegmentContentWidgetSettingsType::class => new ProductSegmentContentWidgetSettingsType(
                        $this->registry,
                        'New Arrivals'
                    ),
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
