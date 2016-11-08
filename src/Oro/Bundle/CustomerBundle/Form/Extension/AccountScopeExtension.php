<?php

namespace Oro\Bundle\CustomerBundle\Form\Extension;

use Oro\Bundle\CustomerBundle\Form\Type\AccountSelectType;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;

class AccountScopeExtension extends AbstractTypeExtension
{
    const SCOPE_FIELD = 'account';

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
                AccountSelectType::NAME,
                [
                    'label' => 'oro.customer.account.entity_label',
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
