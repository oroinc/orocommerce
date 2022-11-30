<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\ContentWidget;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Persistence\ObjectRepository;
use Oro\Bundle\CMSBundle\ContentWidget\TabbedContentWidgetType;
use Oro\Bundle\CMSBundle\Entity\ContentWidget;
use Oro\Bundle\CMSBundle\Entity\TabbedContentItem;
use Oro\Bundle\CMSBundle\Form\Type\TabbedContentItemCollectionType;
use Oro\Bundle\CMSBundle\Form\Type\TabbedContentItemType;
use Oro\Bundle\CMSBundle\Form\Type\WYSIWYGType;
use Oro\Bundle\CMSBundle\Tests\Unit\Form\Type\WysiwygAwareTestTrait;
use Oro\Bundle\FormBundle\Form\Extension\DataBlockExtension;
use Oro\Component\Testing\Unit\Form\Extension\Stub\FormTypeValidatorExtensionStub;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Form\Extension\Core\Type\FormType as SymfonyFormType;
use Twig\Environment;

class TabbedContentWidgetTypeTest extends FormIntegrationTestCase
{
    use WysiwygAwareTestTrait;

    private EntityRepository|\PHPUnit\Framework\MockObject\MockObject $entityRepository;

    private TabbedContentWidgetType $contentWidgetType;

    protected function setUp(): void
    {
        parent::setUp();

        $this->entityRepository = $this->createMock(ObjectRepository::class);

        $managerRegistry = $this->createMock(ManagerRegistry::class);
        $entityManager = $this->createMock(ObjectManager::class);

        $managerRegistry->expects(self::any())
            ->method('getManagerForClass')
            ->with(TabbedContentItem::class)
            ->willReturn($entityManager);

        $managerRegistry->expects(self::any())
            ->method('getRepository')
            ->with(TabbedContentItem::class)
            ->willReturn($this->entityRepository);

        $this->contentWidgetType = new TabbedContentWidgetType($managerRegistry);
    }

    public function testGetLabel(): void
    {
        self::assertEquals('oro.cms.content_widget_type.tabbed_content.label', $this->contentWidgetType->getLabel());
    }

    public function testIsInline(): void
    {
        self::assertFalse($this->contentWidgetType->isInline());
    }

    public function testGetWidgetData(): void
    {
        $contentWidget = new ContentWidget();
        $contentWidget->setName('test_name');

        $items = [new TabbedContentItem()];

        $this->entityRepository
            ->expects(self::any())
            ->method('findBy')
            ->with(['contentWidget' => $contentWidget], ['itemOrder' => 'ASC'])
            ->willReturn($items);

        $expectedData = [
            'instanceNumber' => 0,
            'tabbedContentItems' => new ArrayCollection($items),
        ];

        self::assertEquals($expectedData, $this->contentWidgetType->getWidgetData($contentWidget));
        self::assertEquals(
            array_merge($expectedData, ['instanceNumber' => 1]),
            $this->contentWidgetType->getWidgetData(clone $contentWidget)
        );
    }

    public function testGetSettingsForm(): void
    {
        $contentWidget = new ContentWidget();
        $data = [new TabbedContentItem()];

        $this->entityRepository
            ->expects(self::once())
            ->method('findBy')
            ->with(['contentWidget' => $contentWidget], ['itemOrder' => 'ASC'])
            ->willReturn($data);

        $form = $this->contentWidgetType->getSettingsForm($contentWidget, $this->factory);

        self::assertInstanceOf(SymfonyFormType::class, $form->getConfig()->getType()->getInnerType());
        self::assertTrue($form->has('tabbedContentItems'));
        self::assertInstanceOf(
            TabbedContentItemCollectionType::class,
            $form->get('tabbedContentItems')->getConfig()->getType()->getInnerType()
        );
    }

    public function testGetBackOfficeViewSubBlocks(): void
    {
        $contentWidget = new ContentWidget();
        $contentWidget->setName('test_name');

        $items = [new TabbedContentItem()];

        $this->entityRepository->expects(self::any())
            ->method('findBy')
            ->with(['contentWidget' => $contentWidget], ['itemOrder' => 'ASC'])
            ->willReturn($items);

        $twig = $this->createMock(Environment::class);
        $twig->expects(self::once())
            ->method('render')
            ->willReturnCallback(
                static function ($name, array $context = []) use ($items) {
                    self::assertEquals(
                        [
                            'tabbedContentItems' => new ArrayCollection($items),
                            'instanceNumber' => 0,
                        ],
                        $context
                    );

                    if ($name === '@OroCMS/TabbedContentContentWidget/tabbed_content_items.html.twig') {
                        return 'rendered items template';
                    }

                    return '';
                }
            );

        self::assertEquals(
            [
                [
                    'title' => 'oro.cms.contentwidget.sections.tabbed_content_items.label',
                    'subblocks' => [['data' => ['rendered items template']]],
                ],
            ],
            $this->contentWidgetType->getBackOfficeViewSubBlocks($contentWidget, $twig)
        );
    }

    protected function getExtensions(): array
    {
        return [
            new PreloadedExtension(
                [
                    TabbedContentItemCollectionType::class => new TabbedContentItemCollectionType(),
                    TabbedContentItemType::class => new TabbedContentItemType(),
                    WYSIWYGType::class => $this->createWysiwygType(),
                ],
                [
                    SymfonyFormType::class => [new DataBlockExtension(), new FormTypeValidatorExtensionStub()],
                ]
            ),
        ];
    }

    public function testGetDefaultTemplate(): void
    {
        $contentWidget = new ContentWidget();
        $twig = $this->createMock(Environment::class);

        self::assertEquals('', $this->contentWidgetType->getDefaultTemplate($contentWidget, $twig));
    }
}
