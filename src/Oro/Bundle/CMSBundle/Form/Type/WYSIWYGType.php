<?php

namespace Oro\Bundle\CMSBundle\Form\Type;

use Oro\Bundle\CMSBundle\Provider\HTMLPurifierScopeProvider;
use Oro\Bundle\EntityBundle\Provider\EntityProvider;
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

    private EventSubscriberInterface $digitalAssetTwigTagsEventSubscriber;

    private AssetHelper $assetHelper;

    private EntityProvider $entityProvider;

    public function __construct(
        HtmlTagProvider $htmlTagProvider,
        HTMLPurifierScopeProvider $purifierScopeProvider,
        EventSubscriberInterface $digitalAssetTwigTagsEventSubscriber,
        AssetHelper $assetHelper,
        EntityProvider $entityProvider
    ) {
        $this->htmlTagProvider = $htmlTagProvider;
        $this->purifierScopeProvider = $purifierScopeProvider;
        $this->digitalAssetTwigTagsEventSubscriber = $digitalAssetTwigTagsEventSubscriber;
        $this->assetHelper = $assetHelper;
        $this->entityProvider = $entityProvider;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addEventSubscriber($this->digitalAssetTwigTagsEventSubscriber);
    }

    public function finishView(FormView $view, FormInterface $form, array $options): void
    {
        $dataClass = $form->getConfig()->getOption('entity_class') ?: $form->getRoot()->getConfig()->getDataClass();
        $entityClass = $form->getRoot()->getConfig()->getDataClass();
        $entity = $this->entityProvider->getEntity($entityClass);
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
        $options['page-component']['options']['jsmodules'] = $options['jsmodules'];
        $options['page-component']['options']['autoRender'] = $options['auto_render'];
        $options['page-component']['options']['builderPlugins'] = $options['builder_plugins'];
        $options['page-component']['options']['disableIsolation'] = $options['disable_isolation'];
        $options['page-component']['options']['entityClass'] = $dataClass;
        $options['page-component']['options']['entityLabels'] = [
            'label' => $entity['label'],
            'plural_label' => $entity['plural_label']
        ];
        $options['page-component']['options']['extraStyles'] = [
            ['name' => 'canvas', 'url' => $this->assetHelper->getUrl('build/admin/css/wysiwyg_canvas.css')],
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

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
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
            'disable_isolation' => false,
            'error_bubbling' => true,
            'entity_class' => null,
            'jsmodules' => [],
        ]);
    }

    public function getParent(): string
    {
        return TextareaType::class;
    }
}
