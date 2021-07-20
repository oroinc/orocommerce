<?php

namespace Oro\Bundle\OrderBundle\Form\Type;

use Oro\Bundle\CurrencyBundle\Form\Type\PriceType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Adds a field that indicates whether the user has changed the price.
 * Used when changing the order currency.
 */
class OrderPriceType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('is_price_changed', HiddenType::class, ['mapped' => false]);
    }

    /**
     * {@inheritdoc}
     */
    public function getParent(): string
    {
        return PriceType::class;
    }
}
