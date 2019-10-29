<?php

namespace Oro\Bundle\CMSBundle\Form\Type;

use Oro\Bundle\CMSBundle\Provider\HTMLPurifierScopeProvider;
use Oro\Bundle\FormBundle\Provider\HtmlTagProvider;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Provides WYSIWYG editor functionality.
 */
class WYSIWYGType extends AbstractType
{
    /** @var HtmlTagProvider */
    private $htmlTagProvider;

    /** @var HTMLPurifierScopeProvider */
    private $purifierScopeProvider;

    /**
     * @param HtmlTagProvider $htmlTagProvider
     * @param HTMLPurifierScopeProvider $purifierScopeProvider
     */
    public function __construct(HtmlTagProvider $htmlTagProvider, HTMLPurifierScopeProvider $purifierScopeProvider)
    {
        $this->htmlTagProvider = $htmlTagProvider;
        $this->purifierScopeProvider = $purifierScopeProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        $dataClass = $form->getRoot()->getConfig()->getDataClass();
        $scope = $this->purifierScopeProvider->getScope($dataClass, $form->getName());
        if ($scope) {
            $allowedElements = $this->htmlTagProvider->getAllowedElements($scope);
            $options['page-component']['options']['allow_tags'] = $allowedElements;
        }

        $options['page-component']['options']['stylesInputSelector'] = sprintf(
            '[data-grapesjs-styles="%s"]',
            $form->getName() . WYSIWYGStylesType::TYPE_SUFFIX
        );
        $options['page-component']['options']['propertiesInputSelector'] = sprintf(
            '[data-grapesjs-properties="%s"]',
            $form->getName() . WYSIWYGPropertiesType::TYPE_SUFFIX
        );
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
            'error_bubbling' => true
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
