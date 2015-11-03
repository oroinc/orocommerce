<?php

namespace OroB2B\Bundle\RFPBundle\Tests\Unit\Form\Type\Stub;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

use OroB2B\Bundle\PricingBundle\Form\Type\ProductPriceListAwareSelectType;

class StubProductPriceListAwareSelectType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return ProductPriceListAwareSelectType::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'class' => 'OroB2B\Bundle\ProductBundle\Entity\Product',
            'property' => 'sku',
            'label' => 'orob2b.product.entity_label',
            'create_enabled' => false,
            'grid_name' => 'products-select-grid-frontend',
            'grid_widget_route' => 'orob2b_account_frontend_datagrid_widget',
            'configs' => [
                'route_name' => 'orob2b_frontend_autocomplete_search'
            ]
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return 'entity';
    }
}
