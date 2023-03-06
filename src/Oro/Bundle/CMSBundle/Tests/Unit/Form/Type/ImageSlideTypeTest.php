<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\Form\Type;

use GuzzleHttp\ClientInterface;
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
        $mainImage = $this->getFile(1001);
        $mediumImage = $this->getFile(2002);
        $smallImage = $this->getFile(3003);

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
     * {@inheritDoc}
     */
    protected function getExtensions(): array
    {
        $validator = $this->createMock(ConfigFileValidator::class);
        $validator->expects($this->any())
            ->method('validate')
            ->willReturn(new ConstraintViolationList());

        $fileType = new FileType(
            new ExternalFileFactory($this->createMock(ClientInterface::class))
        );
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
                        2002 => $this->getFile(2002),
                        3003 => $this->getFile(3003),
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
