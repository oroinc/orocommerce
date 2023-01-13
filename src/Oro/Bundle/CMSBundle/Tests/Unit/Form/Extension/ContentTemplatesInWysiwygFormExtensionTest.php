<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\Form\Extension;

use Oro\Bundle\CMSBundle\Form\Extension\ContentTemplatesInWysiwygFormExtension;
use Oro\Bundle\CMSBundle\Form\Type\WYSIWYGType;
use Oro\Bundle\CMSBundle\Provider\ContentTemplatesForWysiwygPreviewProvider;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Exception\UndefinedOptionsException;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class ContentTemplatesInWysiwygFormExtensionTest extends \PHPUnit\Framework\TestCase
{
    private ContentTemplatesForWysiwygPreviewProvider|\PHPUnit\Framework\MockObject\MockObject
        $contentTemplatesForWysiwygPreviewProvider;

    private AuthorizationCheckerInterface|\PHPUnit\Framework\MockObject\MockObject $authorizationChecker;

    private TokenAccessorInterface $tokenAccessor;

    private ContentTemplatesInWysiwygFormExtension $extension;

    protected function setUp(): void
    {
        $this->contentTemplatesForWysiwygPreviewProvider = $this->createMock(
            ContentTemplatesForWysiwygPreviewProvider::class
        );
        $this->authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $this->tokenAccessor = $this->createMock(TokenAccessorInterface::class);

        $this->extension = new ContentTemplatesInWysiwygFormExtension(
            $this->contentTemplatesForWysiwygPreviewProvider,
            $this->authorizationChecker,
            $this->tokenAccessor
        );
    }

    public function testGetExtendedTypes(): void
    {
        self::assertEquals([WYSIWYGType::class], ContentTemplatesInWysiwygFormExtension::getExtendedTypes());
    }

    public function testConfigureOptionsWhenNoToken(): void
    {
        $optionsResolver = new OptionsResolver();
        $this->extension->configureOptions($optionsResolver);

        $this->tokenAccessor
            ->expects(self::once())
            ->method('hasUser')
            ->willReturn(false);

        $this->authorizationChecker
            ->expects(self::never())
            ->method(self::anything());

        self::assertEquals(['content_templates' => ['enabled' => false]], $optionsResolver->resolve([]));
    }

    public function testConfigureOptionsWhenIsNotGranted(): void
    {
        $optionsResolver = new OptionsResolver();
        $this->extension->configureOptions($optionsResolver);

        $this->tokenAccessor
            ->expects(self::once())
            ->method('hasUser')
            ->willReturn(true);

        $this->authorizationChecker
            ->expects(self::once())
            ->method('isGranted')
            ->with('oro_cms_content_template_view')
            ->willReturn(false);

        self::assertEquals(['content_templates' => ['enabled' => false]], $optionsResolver->resolve([]));
    }

    /**
     * @dataProvider getConfigureOptionsWhenValidDataProvider
     */
    public function testConfigureOptionsWhenValid(array $options, array $expectedOptions): void
    {
        $this->tokenAccessor
            ->expects(self::once())
            ->method('hasUser')
            ->willReturn(true);

        $this->authorizationChecker
            ->expects(self::once())
            ->method('isGranted')
            ->with('oro_cms_content_template_view')
            ->willReturn(true);

        $optionsResolver = new OptionsResolver();
        $this->extension->configureOptions($optionsResolver);

        self::assertEquals($expectedOptions, $optionsResolver->resolve($options));
    }

    public function getConfigureOptionsWhenValidDataProvider(): array
    {
        return [
            'empty' => [
                'options' => [],
                'expectedOptions' => [
                    'content_templates' => [
                        'enabled' => true,
                    ],
                ],
            ],
            'set' => [
                'options' => [
                    'content_templates' => [
                        'enabled' => false,
                    ],
                ],
                'expectedOptions' => [
                    'content_templates' => [
                        'enabled' => false,
                    ],
                ],
            ],
        ];
    }

    /**
     * @dataProvider getConfigureOptionsWhenInvalidDataProvider
     */
    public function testConfigureOptionsWhenInvalid(
        array $options,
        string $exceptionClass,
        string $exceptionMessage
    ): void {
        $optionsResolver = new OptionsResolver();
        $this->extension->configureOptions($optionsResolver);

        $this->expectException($exceptionClass);
        $this->expectExceptionMessageMatches($exceptionMessage);

        $optionsResolver->resolve($options);
    }

    public function getConfigureOptionsWhenInvalidDataProvider(): array
    {
        return [
            'wrong content_templates type' => [
                'options' => [
                    'content_templates' => 'string',
                ],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' => '/The nested option "content_templates" with value "string" ' .
                    'is expected to be of type array, but is of type "string"./',
            ],
            'additional fields' => [
                'options' => [
                    'content_templates_2' => [],
                ],
                'exceptionClass' => UndefinedOptionsException::class,
                'exceptionMessage' => '/The option "content_templates_2" does not exist. ' .
                    'Defined options are: "content_templates"./',
            ],
            'additional nested fields' => [
                'options' => [
                    'content_templates' => [
                        'unknown_option' => true,
                    ],
                ],
                'exceptionClass' => UndefinedOptionsException::class,
                'exceptionMessage' => '/The option "content_templates\[unknown_option\]" does not exist. ' .
                    'Defined options are: "enabled"./',
            ],
            'wrong content_templates[enabled] type' => [
                'options' => [
                    'content_templates' => [
                        'enabled' => 'string',
                    ],
                ],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' => '/The option "content_templates\[enabled\]" with value "string" ' .
                    'is expected to be of type "bool", but is of type "string"./',
            ],
        ];
    }

    /**
     * @dataProvider getFinishViewWithoutContentTemplatesDataProvider
     */
    public function testFinishViewWithoutContentTemplates(array $options): void
    {
        $view = new FormView();
        $this->extension->finishView($view, $this->createMock(FormInterface::class), $options);

        $this->contentTemplatesForWysiwygPreviewProvider->expects(self::never())
            ->method('getContentTemplatesList')
            ->withAnyParameters();

        self::assertArrayNotHasKey('data-page-component-options', $view->vars['attr']);
    }

    public function getFinishViewWithoutContentTemplatesDataProvider(): array
    {
        return [
            'no content_templates options' => [
                'options' => [],
            ],
            'disabled content_templates' => [
                'options' => [
                    'content_templates' => [
                        'enabled' => false,
                    ],
                ],
            ],
        ];
    }

    public function testFinishView(): void
    {
        $contentTemplatesList = [
            [
                'id' => 1,
                'name' => 'ContentTemplate1',
            ],
            [
                'id' => 2,
                'name' => 'ContentTemplate2',
            ],
        ];

        $this->contentTemplatesForWysiwygPreviewProvider->expects(self::once())
            ->method('getContentTemplatesList')
            ->willReturn($contentTemplatesList);

        $view = new FormView();
        $this->extension->finishView(
            $view,
            $this->createMock(FormInterface::class),
            [
                'content_templates' => [
                    'enabled' => true,
                ],
                'attr' => [
                    'data-page-component-options' => [],
                ],
            ]
        );

        self::assertEquals(
            [
                'builderPlugins' => [
                    'content-templates' => [
                        'contentTemplatesData' => $contentTemplatesList,
                        'jsmodule' => 'orocms/js/app/grapesjs/plugins/content-templates'
                    ],
                ],
            ],
            json_decode($view->vars['attr']['data-page-component-options'], true)
        );
    }
}
