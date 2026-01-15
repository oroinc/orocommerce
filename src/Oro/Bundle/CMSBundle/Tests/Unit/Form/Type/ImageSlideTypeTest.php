<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\Form\Type;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Form\EventSubscriber\FileSubscriber;
use Oro\Bundle\AttachmentBundle\Form\Type\FileType;
use Oro\Bundle\AttachmentBundle\Form\Type\ImageType;
use Oro\Bundle\AttachmentBundle\Tools\ExternalFileFactory;
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
use Oro\Component\Testing\ReflectionUtil;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Asset\Context\ContextInterface;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Contracts\Translation\TranslatorInterface;

class ImageSlideTypeTest extends FormIntegrationTestCase
{
    private function getFile(int $id): File
    {
        $file = new File();
        ReflectionUtil::setId($file, $id);

        return $file;
    }

    /**
     * @dataProvider submitDataProviderNew
     */
    public function testSubmitNew(array $submittedData, object $expectedData): void
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
        $extraLargeImage = $this->getFile(1001);
        $extraLargeImage2x = $this->getFile(1002);
        $extraLargeImage3x = $this->getFile(1003);

        $largeImage = $this->getFile(2001);
        $largeImage2x = $this->getFile(2002);
        $largeImage3x = $this->getFile(2003);

        $mediumImage = $this->getFile(3001);
        $mediumImage2x = $this->getFile(3002);
        $mediumImage3x = $this->getFile(3003);

        $smallImage = $this->getFile(4001);
        $smallImage2x = $this->getFile(4002);
        $smallImage3x = $this->getFile(4003);

        $expected = new ImageSlide();
        $expected->setContentWidget(new ContentWidget())
            ->setSlideOrder(42)
            ->setUrl('path/to/test')
            ->setDisplayInSameWindow(true)
            ->setAltImageText('test title')
            ->setTextAlignment(ImageSlide::TEXT_ALIGNMENT_TOP_CENTER)
            ->setLoading(ImageSlide::LOADING_LAZY)
            ->setFetchPriority(ImageSlide::FETCH_PRIORITY_AUTO);

        return [
            'minimum data' => [
                'submittedData' => [
                    'slideOrder' => 42,
                    'url' => 'path/to/test',
                    'displayInSameWindow' => true,
                    'altImageText' => 'test title',
                    'textAlignment' => ImageSlide::TEXT_ALIGNMENT_TOP_CENTER,
                    'loading' => ImageSlide::LOADING_LAZY,
                    'fetchPriority' => ImageSlide::FETCH_PRIORITY_AUTO,
                ],
                'expectedData' => $expected,
            ],
            'full data' => [
                'submittedData' => [
                    'slideOrder' => 42,
                    'extraLargeImage' => 1001,
                    'extraLargeImage2x' => 1002,
                    'extraLargeImage3x' => 1003,
                    'largeImage' => 2001,
                    'largeImage2x' => 2002,
                    'largeImage3x' => 2003,
                    'mediumImage' => 3001,
                    'mediumImage2x' => 3002,
                    'mediumImage3x' => 3003,
                    'smallImage' => 4001,
                    'smallImage2x' => 4002,
                    'smallImage3x' => 4003,
                    'url' => 'path/to/test',
                    'displayInSameWindow' => true,
                    'altImageText' => 'test title',
                    'textAlignment' => ImageSlide::TEXT_ALIGNMENT_TOP_CENTER,
                    'text' => 'test content',
                    'header' => 'test header',
                    'loading' => ImageSlide::LOADING_EAGER,
                    'fetchPriority' => ImageSlide::FETCH_PRIORITY_HIGH,
                ],
                'expectedData' => (clone $expected)
                    ->setExtraLargeImage($extraLargeImage)
                    ->setExtraLargeImage2x($extraLargeImage2x)
                    ->setExtraLargeImage3x($extraLargeImage3x)
                    ->setLargeImage($largeImage)
                    ->setLargeImage2x($largeImage2x)
                    ->setLargeImage3x($largeImage3x)
                    ->setMediumImage($mediumImage)
                    ->setMediumImage2x($mediumImage2x)
                    ->setMediumImage3x($mediumImage3x)
                    ->setSmallImage($smallImage)
                    ->setSmallImage2x($smallImage2x)
                    ->setSmallImage3x($smallImage3x)
                    ->setText('test content')
                    ->setHeader('test header')
                    ->setLoading(ImageSlide::LOADING_EAGER)
                    ->setFetchPriority(ImageSlide::FETCH_PRIORITY_HIGH),
            ],
        ];
    }

    #[\Override]
    protected function getExtensions(): array
    {
        $validator = $this->createMock(ConfigFileValidator::class);
        $validator->expects($this->any())
            ->method('validate')
            ->willReturn(new ConstraintViolationList());

        $fileType = new FileType($this->createMock(ExternalFileFactory::class));
        $fileType->setEventSubscriber(new FileSubscriber($validator));

        $htmlTagProvider = $this->createMock(HtmlTagProvider::class);
        $htmlTagProvider->expects($this->any())
            ->method('getAllowedElements')
            ->willReturn(['br', 'a']);

        $htmlTagHelper = new HtmlTagHelper($htmlTagProvider);
        $htmlTagHelper->setTranslator($this->createMock(TranslatorInterface::class));

        return [
            new PreloadedExtension(
                [
                    $fileType,
                    ImageType::class => new ImageTypeStub([
                        1001 => $this->getFile(1001),
                        1002 => $this->getFile(1002),
                        1003 => $this->getFile(1003),
                        2001 => $this->getFile(2001),
                        2002 => $this->getFile(2002),
                        2003 => $this->getFile(2003),
                        3001 => $this->getFile(3001),
                        3002 => $this->getFile(3002),
                        3003 => $this->getFile(3003),
                        4001 => $this->getFile(4001),
                        4002 => $this->getFile(4002),
                        4003 => $this->getFile(4003),
                    ]),
                    ImageSlideCollectionType::class => new ImageSlideCollectionTypeStub(),
                    ImageSlideType::class => new ImageSlideTypeStub(),
                    new OroRichTextType(
                        $this->createMock(ConfigManager::class),
                        $htmlTagProvider,
                        $this->createMock(ContextInterface::class),
                        $htmlTagHelper
                    ),
                ],
                [
                    FormType::class => [new DataBlockExtension()]
                ]
            )
        ];
    }
}
