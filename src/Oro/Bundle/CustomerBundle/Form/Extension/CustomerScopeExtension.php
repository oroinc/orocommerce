<?php

namespace Oro\Bundle\CustomerBundle\Form\Extension;

use Oro\Bundle\CustomerBundle\Form\Type\CustomerSelectType;
use Oro\Bundle\ScopeBundle\Form\Type\ScopeType;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;

class CustomerScopeExtension extends AbstractTypeExtension
{
    const SCOPE_FIELD = 'customer';

    /**
     * @var string
     */
    protected $extendedType;

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if (array_key_exists(self::SCOPE_FIELD, $options['scope_fields'])) {
            $builder->add(
                self::SCOPE_FIELD,
                CustomerSelectType::NAME,
                [
                    'label' => 'oro.customer.customer.entity_label',
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
