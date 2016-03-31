<?php

namespace OroB2B\Bundle\SaleBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

use OroB2B\Bundle\SaleBundle\Entity\QuoteProductOffer;

class QuoteProductDemandOfferChoiceType extends AbstractType
{
    const NAME = 'orob2b_sale_quote_product_demand_offer_choice';

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'expanded' => true,
                'data_class' => 'OroB2B\Bundle\SaleBundle\Entity\QuoteProductOffer',
                'multiple' => false,
                'choices_as_values' => true,
                'choice_label' => function ($value) {
                    $label = '';
                    // TODO Use unit formatter and translator
                    if ($value instanceof QuoteProductOffer) {
                        $label = $value->getQuantity() . ' ' . $value->getProductUnitCode();
                        if ($value->isAllowIncrements()) {
                            $label .= ' or more';
                        }
                    }
                    return $label;
                }
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

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return 'choice';
    }
}
