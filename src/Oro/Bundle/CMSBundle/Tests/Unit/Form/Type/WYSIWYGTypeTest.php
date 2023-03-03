<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\Form\Type;

use Oro\Bundle\CMSBundle\Entity\Page;
use Oro\Bundle\CMSBundle\Form\EventSubscriber\DigitalAssetTwigTagsEventSubscriber;
use Oro\Bundle\CMSBundle\Form\Type\WYSIWYGType;
use Oro\Bundle\CMSBundle\Provider\HTMLPurifierScopeProvider;
use Oro\Bundle\CMSBundle\Tools\DigitalAssetTwigTagsConverter;
use Oro\Bundle\CMSBundle\Validator\Constraints\WYSIWYG;
use Oro\Bundle\CMSBundle\Validator\Constraints\WYSIWYGValidator;
use Oro\Bundle\EntityBundle\Provider\EntityProvider;
use Oro\Bundle\FormBundle\Provider\HtmlTagProvider;
use Oro\Bundle\UIBundle\Tools\HtmlTagHelper;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Symfony\Component\Asset\Packages as AssetHelper;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

class WYSIWYGTypeTest extends FormIntegrationTestCase
{
    private HtmlTagProvider|MockObject $htmlTagProvider;

    private HTMLPurifierScopeProvider|MockObject $purifierScopeProvider;

    private EventSubscriberInterface $eventSubscriber;

    private AssetHelper|MockObject $assetHelper;

    private EntityProvider|MockObject $entityProvider;

    protected function setUp(): void
    {
        $this->htmlTagProvider = $this->createMock(HtmlTagProvider::class);
        $this->purifierScopeProvider = $this->createMock(HTMLPurifierScopeProvider::class);
        $digitalAssetTwigTagsConverter = $this->createMock(DigitalAssetTwigTagsConverter::class);
        $this->entityProvider = $this->createMock(EntityProvider::class);
        $digitalAssetTwigTagsConverter->expects(self::any())
            ->method('convertToUrls')
            ->willReturnArgument(0);
        $digitalAssetTwigTagsConverter->expects(self::any())
            ->method('convertToTwigTags')
            ->willReturnArgument(0);
        $this->eventSubscriber = new DigitalAssetTwigTagsEventSubscriber($digitalAssetTwigTagsConverter);
        $this->assetHelper = $this->createMock(AssetHelper::class);
        $this->assetHelper->expects(self::any())
            ->method('getUrl')
            ->willReturnArgument(0);

        parent::setUp();
    }

    public function testGetParent(): void
    {
        $type = new WYSIWYGType(
            $this->htmlTagProvider,
            $this->purifierScopeProvider,
            $this->eventSubscriber,
            $this->assetHelper,
            $this->entityProvider
        );
        self::assertEquals(TextareaType::class, $type->getParent());
    }

    public function testConfigureOptions(): void
    {
        $resolver = $this->createMock(OptionsResolver::class);
        $resolver->expects(self::once())
            ->method('setDefaults')
            ->with([
                'page-component' => [
                    'module' => 'orocms/js/app/grapesjs/grapesjs-editor-component',
                    'options' => [
                        'allow_tags' => [],
                    ],
                ],
                'attr' => [
                    'class' => 'grapesjs-textarea hide',
                    'data-validation-force' => 'true',
                    'autocomplete' => 'off',
                ],
                'auto_render' => true,
                'builder_plugins' => [],
                'error_bubbling' => true,
                'entity_class' => null,
                'disable_isolation' => false,
                'jsmodules' => [],
            ])
            ->willReturnSelf();

        $type = new WYSIWYGType(
            $this->htmlTagProvider,
            $this->purifierScopeProvider,
            $this->eventSubscriber,
            $this->assetHelper,
            $this->entityProvider
        );
        $type->configureOptions($resolver);
    }

    /**
     * @dataProvider submitDataProvider
     */
    public function testSubmit(string $htmlValue, array $allowedElements, bool $isValid): void
    {
        $this->purifierScopeProvider->expects(self::once())
            ->method('getScope')
            ->willReturn('default');

        $this->htmlTagProvider->expects(self::once())
            ->method('getAllowedElements')
            ->with('default')
            ->willReturn($allowedElements);

        $form = $this->factory->create(WYSIWYGType::class, null, [
            'data_class' => Page::class,
            'constraints' => new WYSIWYG(),
        ]);

        $form->submit($htmlValue);
        self::assertEquals($htmlValue, $form->getData());
        self::assertEquals($isValid, $form->isValid());
    }

    public function testFinishView(): void
    {
        $this->purifierScopeProvider->expects(self::once())
            ->method('getScope')
            ->with(Page::class, 'wysiwyg')
            ->willReturn('default');

        $this->htmlTagProvider->expects(self::once())
            ->method('getAllowedElements')
            ->with('default')
            ->willReturn(['h1', 'h2', 'h3']);

        $this->entityProvider
            ->expects(self::once())
            ->method('getEntity')
            ->willReturn([
                "name" => Page::class,
                "label" => "Page",
                "plural_label" => "Pages",
                "icon" => "fa-page",
            ]);

        $view = new FormView();
        $form = $this->factory->create(WYSIWYGType::class, null, ['data_class' => Page::class]);
        $type = new WYSIWYGType(
            $this->htmlTagProvider,
            $this->purifierScopeProvider,
            $this->eventSubscriber,
            $this->assetHelper,
            $this->entityProvider
        );
        $type->finishView($view, $form, [
            'page-component' => [
                'module' => 'component/module',
            ],
            'auto_render' => true,
            'builder_plugins' => [
                'bar-plugin' => [
                    'foo' => 'baz',
                ],
            ],
            'disable_isolation' => true,
            'jsmodules' => [],
        ]);

        self::assertEquals('wysiwyg', $view->vars['attr']['data-grapesjs-field']);
        self::assertEquals('component/module', $view->vars['attr']['data-page-component-module']);
        self::assertEquals(
            '{"allow_tags":["h1","h2","h3"]'
            . ',"allowed_iframe_domains":[]'
            . ',"jsmodules":[]'
            . ',"autoRender":true'
            . ',"builderPlugins":{"bar-plugin":{"foo":"baz"}}'
            . ',"disableIsolation":true'
            . ',"entityClass":"Oro\\\\Bundle\\\\CMSBundle\\\\Entity\\\\Page"'
            . ',"entityLabels":{"label":"Page","plural_label":"Pages"}'
            . ',"extraStyles":[{"name":"canvas","url":"build\/admin\/css\/wysiwyg_canvas.css"}]'
            . ',"stylesInputSelector":"[data-grapesjs-styles=\"wysiwyg_style\"]"'
            . ',"propertiesInputSelector":"[data-grapesjs-properties=\"wysiwyg_properties\"]"}',
            $view->vars['attr']['data-page-component-options']
        );
    }

    public function testFinishViewWithEntityClassOption(): void
    {
        $this->purifierScopeProvider->expects(self::once())
            ->method('getScope')
            ->with(Page::class, 'wysiwyg')
            ->willReturn('default');

        $this->htmlTagProvider->expects(self::once())
            ->method('getAllowedElements')
            ->with('default')
            ->willReturn(['h1', 'h2', 'h3']);

        $this->entityProvider
            ->expects(self::once())
            ->method('getEntity')
            ->willReturn([
                "name" => Page::class,
                "label" => "Page",
                "plural_label" => "Pages",
                "icon" => "fa-page",
            ]);

        $view = new FormView();
        $form = $this->factory->create(WYSIWYGType::class, null, ['entity_class' => Page::class]);
        $type = new WYSIWYGType(
            $this->htmlTagProvider,
            $this->purifierScopeProvider,
            $this->eventSubscriber,
            $this->assetHelper,
            $this->entityProvider
        );
        $type->finishView($view, $form, [
            'page-component' => [
                'module' => 'component/module',
            ],
            'auto_render' => true,
            'builder_plugins' => [
                'bar-plugin' => [
                    'foo' => 'baz',
                ],
            ],
            'disable_isolation' => true,
            'jsmodules' => [],
        ]);

        self::assertEquals('wysiwyg', $view->vars['attr']['data-grapesjs-field']);
        self::assertEquals('component/module', $view->vars['attr']['data-page-component-module']);
        self::assertEquals(
            '{"allow_tags":["h1","h2","h3"]'
            . ',"allowed_iframe_domains":[]'
            . ',"jsmodules":[]'
            . ',"autoRender":true'
            . ',"builderPlugins":{"bar-plugin":{"foo":"baz"}}'
            . ',"disableIsolation":true'
            . ',"entityClass":"Oro\\\\Bundle\\\\CMSBundle\\\\Entity\\\\Page"'
            . ',"entityLabels":{"label":"Page","plural_label":"Pages"}'
            . ',"extraStyles":[{"name":"canvas","url":"build\/admin\/css\/wysiwyg_canvas.css"}]'
            . ',"stylesInputSelector":"[data-grapesjs-styles=\"wysiwyg_style\"]"'
            . ',"propertiesInputSelector":"[data-grapesjs-properties=\"wysiwyg_properties\"]"}',
            $view->vars['attr']['data-page-component-options']
        );
    }

    public function testFinishViewForEmptyScope(): void
    {
        $this->purifierScopeProvider->expects(self::once())
            ->method('getScope')
            ->with(Page::class, 'wysiwyg')
            ->willReturn(null);

        $this->entityProvider
            ->expects(self::once())
            ->method('getEntity')
            ->with(Page::class)
            ->willReturn([
                "name" => Page::class,
                "label" => "Page",
                "plural_label" => "Pages",
                "icon" => "fa-page",
            ]);

        $this->htmlTagProvider->expects(self::never())
            ->method('getAllowedElements');

        $view = new FormView();
        $form = $this->factory->create(WYSIWYGType::class, null, ['data_class' => Page::class]);
        $type = new WYSIWYGType(
            $this->htmlTagProvider,
            $this->purifierScopeProvider,
            $this->eventSubscriber,
            $this->assetHelper,
            $this->entityProvider
        );
        $type->finishView($view, $form, [
            'page-component' => [
                'module' => 'component/module',
            ],
            'auto_render' => true,
            'builder_plugins' => [
                'bar-plugin' => [
                    'foo' => 'baz',
                ],
            ],
            'disable_isolation' => true,
            'jsmodules' => [],
        ]);

        self::assertEquals('component/module', $view->vars['attr']['data-page-component-module']);
        self::assertEquals(
            '{"allow_tags":false'
            . ',"allowed_iframe_domains":false'
            . ',"jsmodules":[]'
            . ',"autoRender":true'
            . ',"builderPlugins":{"bar-plugin":{"foo":"baz"}}'
            . ',"disableIsolation":true'
            . ',"entityClass":"Oro\\\\Bundle\\\\CMSBundle\\\\Entity\\\\Page"'
            . ',"entityLabels":{"label":"Page","plural_label":"Pages"}'
            . ',"extraStyles":[{"name":"canvas","url":"build\/admin\/css\/wysiwyg_canvas.css"}]'
            . ',"stylesInputSelector":"[data-grapesjs-styles=\"wysiwyg_style\"]"'
            . ',"propertiesInputSelector":"[data-grapesjs-properties=\"wysiwyg_properties\"]"}',
            $view->vars['attr']['data-page-component-options']
        );
    }

    public function submitDataProvider(): array
    {
        return [
            'valid' => [
                'htmlValue' => '<h1>Heading text</h1><p>Body text</p>',
                'allowedElements' => ['h1', 'p'],
                'isValid' => true,
            ],
            'invalid' => [
                'htmlValue' => '<h1>Heading text</h1><p>Body text</p>',
                'allowedElements' => ['h1'],
                'isValid' => false,
            ],
        ];
    }

    /**
     * {@inheritDoc}
     */
    protected function getExtensions(): array
    {
        return [
            new PreloadedExtension(
                [
                    WYSIWYGType::class => new WYSIWYGType(
                        $this->htmlTagProvider,
                        $this->purifierScopeProvider,
                        $this->eventSubscriber,
                        $this->assetHelper,
                        $this->entityProvider
                    ),
                ],
                []
            ),
            $this->getValidatorExtension(true),
        ];
    }

    /**
     * {@inheritDoc}
     */
    protected function getValidators(): array
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $logger = $this->createMock(LoggerInterface::class);
        $htmlTagHelper = new HtmlTagHelper($this->htmlTagProvider);
        $htmlTagHelper->setTranslator($translator);

        return [
            WYSIWYGValidator::class => new WYSIWYGValidator(
                $htmlTagHelper,
                $this->purifierScopeProvider,
                $translator,
                $logger
            ),
        ];
    }
}
