<?php

namespace Oro\Bundle\ConsentBundle\Form\Extension;

use Oro\Bundle\QueryDesignerBundle\Form\Type\FieldChoiceType;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

/**
 * Excludes "acceptedConsents" field from selects for Columns and Grouping designer sections
 * But not from Filters!
 */
class FieldChoiceTypeExtension extends AbstractTypeExtension
{
    /**
     * {@inheritdoc}
     */
    public function getExtendedType()
    {
        return FieldChoiceType::class;
    }

    /**
     * @param FormView $view
     * @param FormInterface $form
     * @param array $options
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['page_component_options']['exclude'][] = [
            'name' => 'acceptedConsents',
        ] ;
    }
}
