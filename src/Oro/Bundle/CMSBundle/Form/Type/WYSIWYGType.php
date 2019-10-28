<?php

namespace Oro\Bundle\CMSBundle\Form\Type;

use Oro\Bundle\CMSBundle\Validator\Constraints\WYSIWYG;
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

    /**
     * @param HtmlTagProvider $htmlTagProvider
     */
    public function __construct(HtmlTagProvider $htmlTagProvider)
    {
        $this->htmlTagProvider = $htmlTagProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function finishView(FormView $view, FormInterface $form, array $options)
    {
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
                    'allow_tags' => $this->htmlTagProvider->getAllowedElements(WYSIWYG::HTML_PURIFIER_SCOPE)
                ]
            ],
            'constraints' => [
                new WYSIWYG()
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
