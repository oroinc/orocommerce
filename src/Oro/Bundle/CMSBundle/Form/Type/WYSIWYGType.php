<?php

namespace Oro\Bundle\CMSBundle\Form\Type;

use Oro\Bundle\CMSBundle\Form\DataTransformer\DigitalAssetTwigTagsTransformer;
use Oro\Bundle\CMSBundle\Provider\HTMLPurifierScopeProvider;
use Oro\Bundle\CMSBundle\Tools\DigitalAssetTwigTagsConverter;
use Oro\Bundle\FormBundle\Provider\HtmlTagProvider;
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

    public function __construct(
        HtmlTagProvider $htmlTagProvider,
        HTMLPurifierScopeProvider $purifierScopeProvider,
        DigitalAssetTwigTagsConverter $digitalAssetTwigTagsConverter
    ) {
        $this->htmlTagProvider = $htmlTagProvider;
        $this->purifierScopeProvider = $purifierScopeProvider;
        $this->digitalAssetTwigTagsConverter = $digitalAssetTwigTagsConverter;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addViewTransformer(new DigitalAssetTwigTagsTransformer($this->digitalAssetTwigTagsConverter));
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
        $options['page-component']['options']['entityClass'] = $dataClass;
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
        $view->vars['attr']['data-page-component-options'] = json_encode($options['page-component']['options']);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
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
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return TextareaType::class;
    }
}
