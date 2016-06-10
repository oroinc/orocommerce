<?php

namespace OroB2B\Bundle\PricingBundle\Form\Type;

use OroB2B\Bundle\PricingBundle\Entity\PriceAttributeProductPrice;

use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProductAttributePriceCollectionType extends FormType
{
    const NAME = 'orob2b_pricing_product_attribute_price_collection';

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        /** @var PriceAttributeProductPrice $price */
        $price = current($form->getData());
        $currencies = [];
        $units = [];

        if ($price) {
            $basePriceList = $price->getPriceList();
            $currencies = $basePriceList->getCurrencies();
            $units = $price->getProduct()->getAvailableUnitCodes();
        }

        $view->vars['currencies'] = $currencies;
        $view->vars['units'] = $units;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'type' => ProductAttributePriceType::NAME,
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return 'collection';
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }
}
