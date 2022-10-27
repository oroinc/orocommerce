<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\ContentWidget;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Persistence\ObjectRepository;
use GuzzleHttp\ClientInterface;
use Oro\Bundle\AttachmentBundle\Form\Type\FileType;
use Oro\Bundle\AttachmentBundle\Tools\ExternalFileFactory;
use Oro\Bundle\CMSBundle\ContentWidget\ImageSliderContentWidgetType;
use Oro\Bundle\CMSBundle\Entity\ContentWidget;
use Oro\Bundle\CMSBundle\Entity\ImageSlide;
use Oro\Bundle\CMSBundle\Form\Type\ImageSlideCollectionType;
use Oro\Bundle\CMSBundle\Form\Type\ImageSlideType;
use Oro\Bundle\CMSBundle\Tests\Unit\ContentWidget\Stub\ImageSlideCollectionTypeStub;
use Oro\Bundle\CMSBundle\Tests\Unit\ContentWidget\Stub\ImageSlideTypeStub;
use Oro\Bundle\CMSBundle\Tests\Unit\Entity\Stub\ImageSlide as ImageSlideStub;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\FormBundle\Form\Extension\DataBlockExtension;
use Oro\Bundle\FormBundle\Form\Type\OroRichTextType;
use Oro\Bundle\FormBundle\Provider\HtmlTagProvider;
use Oro\Bundle\UIBundle\Tools\HtmlTagHelper;
use Oro\Component\Testing\Unit\Form\Extension\Stub\FormTypeValidatorExtensionStub;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Asset\Context\ContextInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\Extension\Core\Type\FormType as SymfonyFormType;
use Twig\Environment;

class ImageSliderContentWidgetTypeTest extends FormIntegrationTestCase
{
    /** @var ObjectRepository|\PHPUnit\Framework\MockObject\MockObject */
    private $repository;

    /** @var ObjectManager|\PHPUnit\Framework\MockObject\MockObject */
    private $manager;

    /** @var ImageSliderContentWidgetType */
    private $contentWidgetType;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->createMock(ObjectRepository::class);

        $this->manager = $this->createMock(ObjectManager::class);
        $this->manager->expects($this->any())
            ->method('getRepository')
            ->with(ImageSlide::class)
            ->willReturn($this->repository);

        $registry = $this->createMock(ManagerRegistry::class);
        $registry->expects($this->any())
            ->method('getManagerForClass')
            ->with(ImageSlide::class)
            ->willReturn($this->manager);

        $this->contentWidgetType = new ImageSliderContentWidgetType($registry);
    }

    public function testGetName(): void
    {
        $this->assertEquals('image_slider', $this->contentWidgetType::getName());
    }

    public function testGetLabel(): void
    {
        $this->assertEquals('oro.cms.content_widget_type.image_slider.label', $this->contentWidgetType->getLabel());
    }

    public function testIsInline(): void
    {
        $this->assertFalse($this->contentWidgetType->isInline());
    }

    public function testGetWidgetData(): void
    {
        $contentWidget = new ContentWidget();
        $contentWidget->setName('test_name');

        $slides = [new ImageSlide()];

        $this->repository->expects($this->any())
            ->method('findBy')
            ->with(['contentWidget' => $contentWidget], ['slideOrder' => 'ASC'])
            ->willReturn($slides);

        $expectedData = [
            'contentWidgetName' => 'test_name',
            'pageComponentName' => 'test_name',
            'pageComponentOptions' => new ArrayCollection(
                [
                    'slidesToShow' => 1,
                    'slidesToScroll' => 1,
                    'autoplay' => true,
                    'autoplaySpeed' => 4000,
                    'arrows' => false,
                    'dots' => true,
                    'infinite' => false,
                ]
            ),
            'imageSlides' => new ArrayCollection($slides),
        ];

        $this->assertEquals($expectedData, $this->contentWidgetType->getWidgetData($contentWidget));
        $this->assertEquals(
            array_merge($expectedData, ['pageComponentName' => 'test_name1']),
            $this->contentWidgetType->getWidgetData(clone $contentWidget)
        );
        $this->assertEquals(
            array_merge($expectedData, ['pageComponentName' => 'test_name2']),
            $this->contentWidgetType->getWidgetData($contentWidget)
        );
    }

    public function testGetSettingsForm(): void
    {
        $contentWidget = new ContentWidget();
        $data = [new ImageSlideStub()];

        $this->repository->expects($this->once())
            ->method('findBy')
            ->with(['contentWidget' => $contentWidget], ['slideOrder' => 'ASC'])
            ->willReturn($data);

        $form = $this->contentWidgetType->getSettingsForm($contentWidget, $this->factory);

        $this->assertInstanceOf(SymfonyFormType::class, $form->getConfig()->getType()->getInnerType());
        $this->assertTrue($form->has('slidesToShow'));
        $this->assertTrue($form->has('slidesToScroll'));
        $this->assertTrue($form->has('autoplay'));
        $this->assertTrue($form->has('autoplaySpeed'));
        $this->assertTrue($form->has('arrows'));
        $this->assertTrue($form->has('dots'));
        $this->assertTrue($form->has('infinite'));
        $this->assertTrue($form->has('imageSlides'));
        $this->assertInstanceOf(
            ImageSlideCollectionType::class,
            $form->get('imageSlides')->getConfig()->getType()->getInnerType()
        );
    }

    public function testGetBackOfficeViewSubBlocks(): void
    {
        $contentWidget = new ContentWidget();
        $contentWidget->setName('test_name');

        $slides = [new ImageSlide()];

        $this->repository->expects($this->any())
            ->method('findBy')
            ->with(['contentWidget' => $contentWidget], ['slideOrder' => 'ASC'])
            ->willReturn($slides);

        $twig = $this->createMock(Environment::class);
        $twig->expects($this->exactly(2))
            ->method('render')
            ->willReturnCallback(
                function ($name, array $context = []) use ($slides) {
                    $this->assertEquals(
                        [
                            'contentWidgetName' => 'test_name',
                            'pageComponentName' => 'test_name',
                            'pageComponentOptions' => new ArrayCollection(
                                [
                                    'slidesToShow' => 1,
                                    'slidesToScroll' => 1,
                                    'autoplay' => true,
                                    'autoplaySpeed' => 4000,
                                    'arrows' => false,
                                    'dots' => true,
                                    'infinite' => false,
                                ]
                            ),
                            'imageSlides' => new ArrayCollection($slides),
                        ],
                        $context
                    );

                    if ($name === '@OroCMS/ImageSliderContentWidget/slider_options.html.twig') {
                        return 'rendered settings template';
                    }

                    if ($name === '@OroCMS/ImageSliderContentWidget/image_slides.html.twig') {
                        return 'rendered slides template';
                    }
                }
            );

        $this->assertEquals(
            [
                [
                    'title' => 'oro.cms.contentwidget.sections.slider_options.label',
                    'subblocks' => [['data' => ['rendered settings template']]]
                ],
                [
                    'title' => 'oro.cms.contentwidget.sections.image_slides.label',
                    'subblocks' => [['data' => ['rendered slides template']]]
                ]
            ],
            $this->contentWidgetType->getBackOfficeViewSubBlocks($contentWidget, $twig)
        );
    }

    protected function getExtensions(): array
    {
        $fileType = new FileType(
            new ExternalFileFactory($this->createMock(ClientInterface::class))
        );
        $fileType->setEventSubscriber(
            new class() implements EventSubscriberInterface {
                /** {@inheritdoc} */
                public static function getSubscribedEvents(): array
                {
                    return [];
                }
            }
        );

        /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject $configManager */
        $configManager = $this->createMock(ConfigManager::class);

        /** @var HtmlTagProvider|\PHPUnit\Framework\MockObject\MockObject $htmlTagProvider */
        $htmlTagProvider = $this->createMock(HtmlTagProvider::class);
        $htmlTagProvider->expects($this->any())
            ->method('getAllowedElements')
            ->willReturn(['br', 'a']);

        $htmlTagHelper = new HtmlTagHelper($htmlTagProvider);

        /** @var ContextInterface|\PHPUnit\Framework\MockObject\MockObject $context */
        $context = $this->createMock(ContextInterface::class);

        return [
            new PreloadedExtension(
                [
                    $fileType,
                    ImageSlideCollectionType::class => new ImageSlideCollectionTypeStub(),
                    ImageSlideType::class => new ImageSlideTypeStub(),
                    new OroRichTextType($configManager, $htmlTagProvider, $context, $htmlTagHelper),
                ],
                [
                    SymfonyFormType::class => [new DataBlockExtension(), new FormTypeValidatorExtensionStub()]
                ]
            )
        ];
    }

    public function testGetDefaultTemplate(): void
    {
        $contentWidget = new ContentWidget();
        $twig = $this->createMock(Environment::class);

        $this->assertEquals('', $this->contentWidgetType->getDefaultTemplate($contentWidget, $twig));
    }
}
