<?php

namespace OroB2B\Bundle\SaleBundle\Form\Type;

use OroB2B\Bundle\SaleBundle\Entity\QuoteDemand;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class QuoteDemandType extends AbstractType
{
    const NAME = 'orob2b_sale_quote_demand';

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired(['data']);
        $resolver->setDefault('data_class', 'OroB2B\Bundle\SaleBundle\Entity\QuoteDemand');
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /** @var QuoteDemand $quoteDemand */
        $quoteDemand = $options['data'];
        $builder->add(
            'demandProducts',
            QuoteProductDemandCollectionType::NAME,
            [
                'data' => $quoteDemand->getDemandProducts()
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }
}
