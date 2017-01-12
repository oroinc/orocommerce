<?php

namespace Oro\Bundle\CustomerBundle\Form\Extension;

use Oro\Bundle\CustomerBundle\Form\Type\CustomerGroupSelectType;
use Oro\Bundle\ScopeBundle\Form\Type\ScopeType;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;

class CustomerGroupScopeExtension extends AbstractTypeExtension
{
    const SCOPE_FIELD = 'customerGroup';
    
    /**
     * @var string
     */
    protected $label;

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if (array_key_exists(self::SCOPE_FIELD, $options['scope_fields'])) {
            $builder->add(
                self::SCOPE_FIELD,
                CustomerGroupSelectType::NAME,
                [
                    'label' => 'oro.customer.customergroup.entity_label',
                    'create_form_route' => null,
                ]
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getExtendedType()
    {
        return ScopeType::class;
    }
}
