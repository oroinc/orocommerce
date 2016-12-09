<?php
namespace Oro\Bundle\CustomerBundle\Tests\Unit\Form\Type\Stub;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class FrontendOwnerSelectTypeStub extends AbstractType
{
    const NAME = 'oro_customer_frontend_owner_select';

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(
            [
                'choice_label' => null,
                'class' => null,
                'targetObject' => null,
                'query_builder' => null,
            ]
        );
    }
}
