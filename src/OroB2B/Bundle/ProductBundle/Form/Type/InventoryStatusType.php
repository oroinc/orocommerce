<?php

namespace OroB2B\Bundle\ProductBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use OroB2B\Bundle\ProductBundle\Model\InventoryStatus;

class InventoryStatusType extends AbstractType
{
    const NAME = 'orob2b_product_inventory_status';

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }

    /**
     * {@inheritDoc}
     */
    public function getParent()
    {
        return 'choice';
    }

    /**
     * {@inheritDoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $choices = [];
        foreach (InventoryStatus::getStatuses() as $status) {
            $choices[$status] = 'orob2b.product.inventory_status.' . $status;
        }

        $resolver->setDefaults(['empty_value' => false, 'choices' => $choices]);
    }
}
