<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\Form\Type;

use Oro\Bundle\CMSBundle\Entity\Page;
use Oro\Bundle\CMSBundle\Form\Type\WYSIWYGType;
use Oro\Bundle\CMSBundle\Provider\HTMLPurifierScopeProvider;
use Oro\Bundle\CMSBundle\Tools\DigitalAssetTwigTagsConverter;
use Oro\Bundle\CMSBundle\Validator\Constraints\WYSIWYG;
use Oro\Bundle\CMSBundle\Validator\Constraints\WYSIWYGValidator;
use Oro\Bundle\FormBundle\Provider\HtmlTagProvider;
use Oro\Bundle\UIBundle\Tools\HtmlTagHelper;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

class WYSIWYGTypeTest extends FormIntegrationTestCase
{
    /** @var HtmlTagProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $htmlTagProvider;

    /** @var HTMLPurifierScopeProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $purifierScopeProvider;

    /** @var DigitalAssetTwigTagsConverter|\PHPUnit\Framework\MockObject\MockObject */
    private $digitalAssetTwigTagsConverter;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->htmlTagProvider = $this->createMock(HtmlTagProvider::class);
        $this->purifierScopeProvider = $this->createMock(HTMLPurifierScopeProvider::class);
        $this->digitalAssetTwigTagsConverter = $this->createMock(DigitalAssetTwigTagsConverter::class);
        $this->digitalAssetTwigTagsConverter->expects(self::any())
            ->method('convertToUrls')
            ->willReturnArgument(0);
        $this->digitalAssetTwigTagsConverter->expects(self::any())
            ->method('convertToTwigTags')
            ->willReturnArgument(0);

        parent::setUp();
    }

    public function testGetParent(): void
    {
        $type = new WYSIWYGType(
            $this->htmlTagProvider,
            $this->purifierScopeProvider,
            $this->digitalAssetTwigTagsConverter
        );
        $this->assertEquals(TextareaType::class, $type->getParent());
    }

    public function testConfigureOptions(): void
    {
        /* @var $resolver OptionsResolver|\PHPUnit\Framework\MockObject\MockObject */
        $resolver = $this->createMock(OptionsResolver::class);
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with([
                'page-component' => [
                    'module' => 'oroui/js/app/components/view-component',
                    'options' => [
                        'view' => 'orocms/js/app/grapesjs/grapesjs-editor-view',
                        'allow_tags' => []
                    ]
                ],
                'attr' => [
                    'class' => 'grapesjs-textarea hide',
                    'data-validation-force' => 'true'
                ],
                'auto_render' => true,
                'error_bubbling' => true,
                'entity_class' => null,
            ])
            ->will($this->returnSelf());

        $type = new WYSIWYGType(
            $this->htmlTagProvider,
            $this->purifierScopeProvider,
            $this->digitalAssetTwigTagsConverter
        );
        $type->configureOptions($resolver);
    }

    /**
     * @dataProvider submitDataProvider
     */
    public function testSubmit(string $htmlValue, array $allowedElements, bool $isValid): void
    {
        $this->purifierScopeProvider
            ->expects($this->once())
            ->method('getScope')
            ->willReturn('default');

        $this->htmlTagProvider
            ->expects($this->once())
            ->method('getAllowedElements')
            ->with('default')
            ->willReturn($allowedElements);

        $form = $this->factory->create(WYSIWYGType::class, null, [
            'data_class' => Page::class,
            'constraints' => new WYSIWYG()
        ]);

        $form->submit($htmlValue);
        $this->assertEquals($htmlValue, $form->getData());
        $this->assertEquals($isValid, $form->isValid());
    }

    public function testFinishView(): void
    {
        $this->purifierScopeProvider
            ->expects($this->once())
            ->method('getScope')
            ->with(Page::class, 'wysiwyg')
            ->willReturn('default');

        $this->htmlTagProvider
            ->expects($this->once())
            ->method('getAllowedElements')
            ->with('default')
            ->willReturn(['h1', 'h2', 'h3']);

        $view = new FormView();
        $form = $this->factory->create(WYSIWYGType::class, null, ['data_class' => Page::class]);
        $type = new WYSIWYGType(
            $this->htmlTagProvider,
            $this->purifierScopeProvider,
            $this->digitalAssetTwigTagsConverter
        );
        $type->finishView($view, $form, [
            'page-component' => [
                'module' => 'component/module',
                'options' => ['view' => 'app/view']
            ],
            'auto_render' => true,
        ]);

        $this->assertEquals('wysiwyg', $view->vars['attr']['data-grapesjs-field']);
        $this->assertEquals('component/module', $view->vars['attr']['data-page-component-module']);
        $this->assertEquals(
            '{"view":"app\/view","allow_tags":["h1","h2","h3"]'
            . ',"allowed_iframe_domains":[]'
            . ',"autoRender":true'
            . ',"entityClass":"Oro\\\\Bundle\\\\CMSBundle\\\\Entity\\\\Page"'
            . ',"stylesInputSelector":"[data-grapesjs-styles=\"wysiwyg_style\"]",'
            . '"propertiesInputSelector":"[data-grapesjs-properties=\"wysiwyg_properties\"]"}',
            $view->vars['attr']['data-page-component-options']
        );
    }

    public function testFinishViewWithEntityClassOption(): void
    {
        $this->purifierScopeProvider
            ->expects($this->once())
            ->method('getScope')
            ->with(Page::class, 'wysiwyg')
            ->willReturn('default');

        $this->htmlTagProvider
            ->expects($this->once())
            ->method('getAllowedElements')
            ->with('default')
            ->willReturn(['h1', 'h2', 'h3']);

        $view = new FormView();
        $form = $this->factory->create(WYSIWYGType::class, null, ['entity_class' => Page::class]);
        $type = new WYSIWYGType(
            $this->htmlTagProvider,
            $this->purifierScopeProvider,
            $this->digitalAssetTwigTagsConverter
        );
        $type->finishView($view, $form, [
            'page-component' => [
                'module' => 'component/module',
                'options' => ['view' => 'app/view']
            ],
            'auto_render' => true,
        ]);

        $this->assertEquals('wysiwyg', $view->vars['attr']['data-grapesjs-field']);
        $this->assertEquals('component/module', $view->vars['attr']['data-page-component-module']);
        $this->assertEquals(
            '{"view":"app\/view","allow_tags":["h1","h2","h3"]'
            . ',"allowed_iframe_domains":[]'
            . ',"autoRender":true'
            . ',"entityClass":"Oro\\\\Bundle\\\\CMSBundle\\\\Entity\\\\Page"'
            . ',"stylesInputSelector":"[data-grapesjs-styles=\"wysiwyg_style\"]",'
            . '"propertiesInputSelector":"[data-grapesjs-properties=\"wysiwyg_properties\"]"}',
            $view->vars['attr']['data-page-component-options']
        );
    }

    public function testFinishViewForEmptyScope(): void
    {
        $this->purifierScopeProvider
            ->expects($this->once())
            ->method('getScope')
            ->with(Page::class, 'wysiwyg')
            ->willReturn(null);

        $this->htmlTagProvider
            ->expects($this->never())
            ->method('getAllowedElements');

        $view = new FormView();
        $form = $this->factory->create(WYSIWYGType::class, null, ['data_class' => Page::class]);
        $type = new WYSIWYGType(
            $this->htmlTagProvider,
            $this->purifierScopeProvider,
            $this->digitalAssetTwigTagsConverter
        );
        $type->finishView($view, $form, [
            'page-component' => [
                'module' => 'component/module',
                'options' => ['view' => 'app/view']
            ],
            'auto_render' => true,
        ]);

        $this->assertEquals('component/module', $view->vars['attr']['data-page-component-module']);
        $this->assertEquals(
            '{"view":"app\/view","allow_tags":false,'
            . '"allowed_iframe_domains":false,'
            . '"autoRender":true,'
            . '"entityClass":"Oro\\\\Bundle\\\\CMSBundle\\\\Entity\\\\Page",'
            . '"stylesInputSelector":"[data-grapesjs-styles=\"wysiwyg_style\"]",'
            . '"propertiesInputSelector":"[data-grapesjs-properties=\"wysiwyg_properties\"]"}',
            $view->vars['attr']['data-page-component-options']
        );
    }

    public function submitDataProvider(): array
    {
        return [
            'valid' => [
                'htmlValue' => '<h1>Heading text</h1><p>Body text</p>',
                'allowedElements' => ['h1', 'p'],
                'isValid' => true
            ],
            'invalid' => [
                'htmlValue' => '<h1>Heading text</h1><p>Body text</p>',
                'allowedElements' => ['h1'],
                'isValid' => false
            ]
        ];
    }

    /**
     * @return array
     */
    protected function getExtensions()
    {
        return [
            new PreloadedExtension(
                [
                    WYSIWYGType::class => new WYSIWYGType(
                        $this->htmlTagProvider,
                        $this->purifierScopeProvider,
                        $this->digitalAssetTwigTagsConverter
                    )
                ],
                []
            ),
            $this->getValidatorExtension(true),
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function getValidators()
    {
        $htmlTagHelper = new HtmlTagHelper($this->htmlTagProvider);
        /** @var TranslatorInterface|\PHPUnit\Framework\MockObject\MockObject $translator */
        $translator = $this->createMock(TranslatorInterface::class);
        $htmlTagHelper->setTranslator($translator);

        $translator = $this->createMock(TranslatorInterface::class);

        /** @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject $logger */
        $logger = $this->createMock(LoggerInterface::class);

        $wysiwygConstraint = new WYSIWYG();

        return [
            $wysiwygConstraint->validatedBy() =>
                new WYSIWYGValidator($htmlTagHelper, $this->purifierScopeProvider, $translator, $logger)
        ];
    }
}
