<?php

namespace OroB2B\Bundle\SaleBundle\Form\Type;

use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use OroB2B\Bundle\SaleBundle\Form\EventListener\QuoteToOrderResizeFormSubscriber;

class QuoteProductDemandCollectionType extends CollectionType
{
    const NAME = 'orob2b_sale_quote_product_demand_collection';

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);

        $resolver->setRequired(['data']);
        $resolver->setDefaults(
            [
                'allow_add' => false,
                'allow_delete' => false,
                'type' => QuoteProductDemandType::NAME
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        // custom subscriber to pass data to child form types
        $resizeSubscriber = new QuoteToOrderResizeFormSubscriber(
            $options['type'],
            $options['options'],
            $options['allow_add'],
            $options['allow_delete'],
            $options['delete_empty']
        );
        $builder->addEventSubscriber($resizeSubscriber);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->getBlockPrefix();
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return self::NAME;
    }
}
