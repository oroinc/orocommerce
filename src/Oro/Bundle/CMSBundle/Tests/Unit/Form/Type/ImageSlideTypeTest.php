<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\Form\Type;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Form\EventSubscriber\FileSubscriber;
use Oro\Bundle\AttachmentBundle\Form\Type\FileType;
use Oro\Bundle\AttachmentBundle\Form\Type\ImageType;
use Oro\Bundle\AttachmentBundle\Validator\ConfigFileValidator;
use Oro\Bundle\CMSBundle\Entity\ContentWidget;
use Oro\Bundle\CMSBundle\Form\Type\ImageSlideCollectionType;
use Oro\Bundle\CMSBundle\Form\Type\ImageSlideType;
use Oro\Bundle\CMSBundle\Tests\Unit\ContentWidget\Stub\ImageSlideCollectionTypeStub;
use Oro\Bundle\CMSBundle\Tests\Unit\ContentWidget\Stub\ImageSlideTypeStub;
use Oro\Bundle\CMSBundle\Tests\Unit\Entity\Stub\ImageSlide;
use Oro\Bundle\CMSBundle\Tests\Unit\Form\Type\Stub\ImageTypeStub;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\FormBundle\Form\Extension\DataBlockExtension;
use Oro\Bundle\FormBundle\Form\Type\OroRichTextType;
use Oro\Bundle\FormBundle\Provider\HtmlTagProvider;
use Oro\Bundle\UIBundle\Tools\HtmlTagHelper;
use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Asset\Context\ContextInterface;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Contracts\Translation\TranslatorInterface;

class ImageSlideTypeTest extends FormIntegrationTestCase
{
    use EntityTrait;

    /**
     * @dataProvider submitDataProviderNew
     *
     * @param array $submittedData
     * @param object $expectedData
     */
    public function testSubmitNew(array $submittedData, $expectedData): void
    {
        $defaultData = new ImageSlide();

        $form = $this->factory->create(ImageSlideType::class, $defaultData, ['content_widget' => new ContentWidget()]);

        $this->assertEquals($defaultData, $form->getData());
        $this->assertEquals($defaultData, $form->getViewData());

        $form->submit($submittedData);
        $this->assertTrue($form->isValid());
        $this->assertTrue($form->isSynchronized());

        $this->assertEquals($expectedData, $form->getData());
    }

    public function submitDataProviderNew(): array
    {
        $mainImage = $this->getEntity(File::class, ['id' => 1001]);
        $mediumImage = $this->getEntity(File::class, ['id' => 2002]);
        $smallImage = $this->getEntity(File::class, ['id' => 3003]);

        $expected = new ImageSlide();
        $expected->setContentWidget(new ContentWidget())
            ->setSlideOrder(42)
            ->setUrl('path/to/test')
            ->setDisplayInSameWindow(true)
            ->setTitle('test title')
            ->setTextAlignment(ImageSlide::TEXT_ALIGNMENT_TOP_CENTER);

        return [
            'minimum data' => [
                'submittedData' => [
                    'slideOrder' => 42,
                    'url' => 'path/to/test',
                    'displayInSameWindow' => true,
                    'title' => 'test title',
                    'textAlignment' => ImageSlide::TEXT_ALIGNMENT_TOP_CENTER,
                ],
                'expectedData' => $expected,
            ],
            'full data' => [
                'submittedData' => [
                    'slideOrder' => 42,
                    'mainImage' => 1001,
                    'mediumImage' => 2002,
                    'smallImage' => 3003,
                    'url' => 'path/to/test',
                    'displayInSameWindow' => true,
                    'title' => 'test title',
                    'textAlignment' => ImageSlide::TEXT_ALIGNMENT_TOP_CENTER,
                    'text' => 'test content'
                ],
                'expectedData' => (clone $expected)
                    ->setMainImage($mainImage)
                    ->setMediumImage($mediumImage)
                    ->setSmallImage($smallImage)
                    ->setText('test content'),
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function getExtensions(): array
    {
        $validator = $this->createMock(ConfigFileValidator::class);
        $validator->expects($this->any())
            ->method('validate')
            ->willReturn(new ConstraintViolationList());

        $fileType = new FileType();
        $fileType->setEventSubscriber(new FileSubscriber($validator));

        /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject $configManager */
        $configManager = $this->createMock(ConfigManager::class);

        /** @var HtmlTagProvider|\PHPUnit\Framework\MockObject\MockObject $htmlTagProvider */
        $htmlTagProvider = $this->createMock(HtmlTagProvider::class);
        $htmlTagProvider->expects($this->any())
            ->method('getAllowedElements')
            ->willReturn(['br', 'a']);

        $htmlTagHelper = new HtmlTagHelper($htmlTagProvider);

        /** @var TranslatorInterface|\PHPUnit\Framework\MockObject\MockObject $translator */
        $translator = $this->createMock(TranslatorInterface::class);
        $htmlTagHelper->setTranslator($translator);

        /** @var ContextInterface|\PHPUnit\Framework\MockObject\MockObject $context */
        $context = $this->createMock(ContextInterface::class);

        return [
            new PreloadedExtension(
                [
                    $fileType,
                    ImageType::class => new ImageTypeStub(
                        [
                            1001 => $this->getEntity(File::class, ['id' => 1001]),
                            2002 => $this->getEntity(File::class, ['id' => 2002]),
                            3003 => $this->getEntity(File::class, ['id' => 3003]),
                        ],
                        'oro_image'
                    ),
                    ImageSlideCollectionType::class => new ImageSlideCollectionTypeStub(),
                    ImageSlideType::class => new ImageSlideTypeStub(),
                    new OroRichTextType($configManager, $htmlTagProvider, $context, $htmlTagHelper),
                ],
                [
                    FormType::class => [new DataBlockExtension()]
                ]
            )
        ];
    }
}
