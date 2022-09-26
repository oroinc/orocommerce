<?php

namespace Oro\Bundle\CMSBundle\Form\Type;

use Oro\Bundle\CMSBundle\Provider\HTMLPurifierScopeProvider;
use Oro\Bundle\CMSBundle\Tools\DigitalAssetTwigTagsConverter;
use Oro\Bundle\FormBundle\Provider\HtmlTagProvider;
use Symfony\Component\Asset\Packages as AssetHelper;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Provides WYSIWYG editor functionality.
 */
class WYSIWYGType extends AbstractType
{
    private HtmlTagProvider $htmlTagProvider;

    private HTMLPurifierScopeProvider $purifierScopeProvider;

    private DigitalAssetTwigTagsConverter $digitalAssetTwigTagsConverter;

    private ?EventSubscriberInterface $digitalAssetTwigTagsEventSubscriber = null;

    private ?AssetHelper $assetHelper = null;

    public function __construct(
        HtmlTagProvider $htmlTagProvider,
        HTMLPurifierScopeProvider $purifierScopeProvider,
        DigitalAssetTwigTagsConverter $digitalAssetTwigTagsConverter
    ) {
        $this->htmlTagProvider = $htmlTagProvider;
        $this->purifierScopeProvider = $purifierScopeProvider;
        $this->digitalAssetTwigTagsConverter = $digitalAssetTwigTagsConverter;
    }

    public function setDigitalAssetTwigTagsEventSubscriber(
        ?EventSubscriberInterface $digitalAssetTwigTagsEventSubscriber
    ): void {
        $this->digitalAssetTwigTagsEventSubscriber = $digitalAssetTwigTagsEventSubscriber;
    }

    public function setAssetHelper(?AssetHelper $assetHelper): void
    {
        $this->assetHelper = $assetHelper;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventSubscriber(
            $this->digitalAssetTwigTagsEventSubscriber
                ?? new DigitalAssetTwigTagsEventSubscriber($this->digitalAssetTwigTagsConverter)
        );
    }

    /**
     * {@inheritdoc}
     */
    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        $dataClass = $form->getConfig()->getOption('entity_class') ?: $form->getRoot()->getConfig()->getDataClass();
        $fieldName = $form->getName();
        $scope = $dataClass ? $this->purifierScopeProvider->getScope($dataClass, $fieldName) : null;
        if ($scope) {
            $allowedElements = $this->htmlTagProvider->getAllowedElements($scope);
            $allowedIframeDomains = $this->htmlTagProvider->getAllowedIframeDomains($scope);
        } else {
            $allowedElements = false;
            $allowedIframeDomains = false;
        }

        $options['page-component']['options']['allow_tags'] = $allowedElements;
        $options['page-component']['options']['allowed_iframe_domains'] = $allowedIframeDomains;
        $options['page-component']['options']['autoRender'] = $options['auto_render'];
        $options['page-component']['options']['builderPlugins'] = $options['builder_plugins'];
        $options['page-component']['options']['disableIsolation'] = $options['disable_isolation'];
        $options['page-component']['options']['entityClass'] = $dataClass;
        $options['page-component']['options']['extraStyles'] = [
            ['name' => 'canvas', 'url' => $this->getAssetUrl('build/admin/css/wysiwyg_canvas.css')],
        ];
        $options['page-component']['options']['stylesInputSelector'] = sprintf(
            '[data-grapesjs-styles="%s"]',
            $form->getName() . WYSIWYGStylesType::TYPE_SUFFIX
        );
        $options['page-component']['options']['propertiesInputSelector'] = sprintf(
            '[data-grapesjs-properties="%s"]',
            $form->getName() . WYSIWYGPropertiesType::TYPE_SUFFIX
        );
        $view->vars['attr']['data-grapesjs-field'] = $fieldName;
        $view->vars['attr']['data-page-component-module'] = $options['page-component']['module'];
        $view->vars['attr']['data-page-component-options'] = json_encode(
            $options['page-component']['options'],
            JSON_THROW_ON_ERROR
        );
    }

    private function getAssetUrl(string $path): string
    {
        return $this->assetHelper ? $this->assetHelper->getUrl($path) : $path;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'page-component' => [
                'module' => 'oroui/js/app/components/view-component',
                'options' => [
                    'view' => 'orocms/js/app/grapesjs/grapesjs-editor-view',
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
            'disable_isolation' => false,
            'error_bubbling' => true,
            'entity_class' => null,
        ]);
    }

    public function getParent(): string
    {
        return TextareaType::class;
    }
}
