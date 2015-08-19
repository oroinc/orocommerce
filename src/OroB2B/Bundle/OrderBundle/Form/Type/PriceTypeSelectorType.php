<?php

namespace OroB2B\Bundle\OrderBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

use OroB2B\Bundle\OrderBundle\Entity\OrderLineItem;

class PriceTypeSelectorType extends AbstractType
{
    const NAME = 'orob2b_order_price_type';

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'choices' => $this->getChoices(),
                'expanded' => true
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return 'choice';
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }

    /**
     * @return array
     */
    protected function getChoices()
    {
        $choices = [];
        foreach (OrderLineItem::getPriceTypes() as $key => $code) {
            $choices[$key] = sprintf('orob2b.order.orderlineitem.price_type.%s', $code);
        }

        return $choices;
    }
}
