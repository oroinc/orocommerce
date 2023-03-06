<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\Form\Type;

use Oro\Bundle\AttachmentBundle\Form\Type\ImageType;
use Oro\Bundle\AttachmentBundle\Tests\Unit\Fixtures\TestFile;
use Oro\Bundle\CMSBundle\Entity\ContentTemplate;
use Oro\Bundle\CMSBundle\Form\Type\ContentTemplateType;
use Oro\Bundle\CMSBundle\Provider\HTMLPurifierScopeProvider;
use Oro\Bundle\CMSBundle\Tests\Unit\Entity\Stub\ContentTemplateStub;
use Oro\Bundle\CMSBundle\Tests\Unit\Form\Type\Stub\ImageTypeStub;
use Oro\Bundle\CMSBundle\Tests\Unit\Form\Type\Stub\TagSelectTypeStub;
use Oro\Bundle\CMSBundle\Validator\Constraints\TwigContentValidator;
use Oro\Bundle\CMSBundle\Validator\Constraints\WYSIWYGValidator;
use Oro\Bundle\TagBundle\Form\Type\TagSelectType;
use Oro\Bundle\UIBundle\Tools\HtmlTagHelper;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;
use Twig\Template;
use Twig\TemplateWrapper;

class ContentTemplateTypeTest extends FormIntegrationTestCase
{
    use WysiwygAwareTestTrait;

    /**
     * {@inheritDoc}
     */
    protected function getExtensions(): array
    {
        return [
            new PreloadedExtension(
                [
                    TagSelectType::class => new TagSelectTypeStub([]),
                    $this->createWysiwygType(),
                    ImageType::class => new ImageTypeStub([
                        1001 => (new TestFile())->setId(1001),
                        1002 => (new TestFile())->setId(1002),
                    ]),
                ],
                []
            ),
            $this->getValidatorExtension(true)
        ];
    }

    /**
     * {@inheritDoc}
     */
    protected function getValidators(): array
    {
        $htmlTagHelper = $this->createMock(HtmlTagHelper::class);
        $purifierScopeProvider = $this->createMock(HTMLPurifierScopeProvider::class);
        $translator = $this->createMock(TranslatorInterface::class);
        $logger = $this->createMock(LoggerInterface::class);

        $template = $this->createMock(Template::class);
        $template->expects(self::any())
            ->method('render')
            ->willReturn('template');

        $env = $this->createMock(Environment::class);
        $env->expects(self::any())
            ->method('createTemplate')
            ->willReturn(new TemplateWrapper($env, $template));

        return [
            TwigContentValidator::class => new TwigContentValidator($env),
            WYSIWYGValidator::class => new WYSIWYGValidator(
                $htmlTagHelper,
                $purifierScopeProvider,
                $translator,
                $logger
            )
        ];
    }

    public function testBuildForm(): void
    {
        $form = $this->factory->create(ContentTemplateType::class);

        $this->assertFormContainsField('name', $form);
        $this->assertFormContainsField('content', $form);
        $this->assertFormContainsField('tags', $form);
        $this->assertFormContainsField('enabled', $form);
        $this->assertFormContainsField('previewImage', $form);
    }

    /**
     * @dataProvider submitSuccessDataProvider
     */
    public function testSuccessSubmit(
        ContentTemplate $existingData,
        array $requestData,
        ?ContentTemplate $expectedData
    ): void {
        $form = $this->factory->create(ContentTemplateType::class, $existingData);

        self::assertEquals($existingData, $form->getData());

        $form->submit($requestData);

        self::assertTrue($form->isSubmitted());
        self::assertTrue($form->isSynchronized());
        self::assertTrue($form->isValid());

        self::assertEquals($expectedData, $form->getData());
    }


    public function testFailureSubmit(): void
    {
        $existingData = new ContentTemplateStub();
        $requestData = [
            'name' => '',
            'enabled' => true,
            'content' => '',
        ];
        $form = $this->factory->create(ContentTemplateType::class, $existingData);

        self::assertEquals($existingData, $form->getData());

        $form->submit($requestData);

        self::assertTrue($form->isSubmitted());
        self::assertFalse($form->isValid());

        self::assertEquals($existingData, $form->getData());
        self::assertEquals(
            'This value should not be blank.',
            $form
                ->get('name')
                ->getErrors()
                ->current()
                ->getMessage()
        );
    }

    private function submitSuccessDataProvider(): array
    {
        $previewImageFoo = (new TestFile())->setId(1001);
        $previewImageBar = (new TestFile())->setId(1002);

        return [
            'new empty entity' => [
                'existingData' => new ContentTemplateStub(),
                'requestData' => [
                    'name' => 'TestNewEmptyEntityName',
                    'enabled' => true,
                    'content' => 'TestNewEmptyEntityContent',
                ],
                'expectedData' => (new ContentTemplateStub())
                    ->setName('TestNewEmptyEntityName')
                    ->setContent('TestNewEmptyEntityContent')
                    ->setEnabled(true),
            ],
            'existing entity' => [
                'existingData' => (new ContentTemplateStub())
                    ->setName('Test')
                    ->setContent('TestNewEmptyEntityContent')
                    ->setEnabled(false),
                'requestData' => [
                    'name' => 'TestExistingEntityName',
                    'enabled' => true,
                    'content' => 'TestContent',
                ],
                'expectedData' => (new ContentTemplateStub())
                    ->setName('TestExistingEntityName')
                    ->setContent('TestContent')
                    ->setEnabled(true)
            ],
            'new empty entity with preview image' => [
                'existingData' => new ContentTemplateStub(),
                'requestData' => [
                    'name' => 'TestNewEmptyEntityName',
                    'enabled' => true,
                    'content' => 'TestNewEmptyEntityContent',
                    'previewImage' => 1001,
                ],
                'expectedData' => (new ContentTemplateStub())
                    ->setName('TestNewEmptyEntityName')
                    ->setContent('TestNewEmptyEntityContent')
                    ->setEnabled(true)
                    ->setPreviewImage($previewImageFoo)
            ],
            'existing entity with preview image' => [
                'existingData' => (new ContentTemplateStub())
                    ->setName('Test')
                    ->setContent('TestNewEmptyEntityContent')
                    ->setEnabled(false)
                    ->setPreviewImage($previewImageFoo),
                'requestData' => [
                    'name' => 'TestExistingEntityName',
                    'enabled' => true,
                    'content' => 'TestContent',
                    'previewImage' => 1002,
                ],
                'expectedData' => (new ContentTemplateStub())
                    ->setName('TestExistingEntityName')
                    ->setContent('TestContent')
                    ->setEnabled(true)
                    ->setPreviewImage($previewImageBar)
            ]
        ];
    }
}
