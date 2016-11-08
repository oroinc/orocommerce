<?php

namespace Oro\Bundle\CustomerBundle\Form\Extension;

use Oro\Bundle\CustomerBundle\Form\Type\AccountGroupSelectType;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;

class AccountGroupScopeExtension extends AbstractTypeExtension
{
    const SCOPE_FIELD = 'accountGroup';
    
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
                AccountGroupSelectType::NAME,
                [
                    'label' => 'oro.customer.accountgroup.entity_label',
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
        return 'oro_scope';
    }
}
