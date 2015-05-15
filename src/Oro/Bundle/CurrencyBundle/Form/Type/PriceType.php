<?php

namespace Oro\Bundle\CurrencyBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Oro\Bundle\CurrencyBundle\Form\DataTransformer\PriceTransformer;

class PriceType extends AbstractType
{
    const NAME = 'oro_currency_price';

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('value', 'number', ['required' => true])
            ->add(
                'currency',
                CurrencySelectionType::NAME,
                [
                    'currencies_list' => $options['currencies_list'],
                    'compact' => $options['compact'],
                    'required' => true
                ]
            )
        ;

        $builder->addViewTransformer(new PriceTransformer());
    }

    /**
     * @inheritdoc
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults([
            'data_class' => 'Oro\Bundle\CurrencyBundle\Model\Price',
            'cascade_validation' => true,
            'currencies_list' => null,
            'compact' => false
        ]);
    }

    /**
     * @return string
     */
    public function getName()
    {
        return self::NAME;
    }
}
