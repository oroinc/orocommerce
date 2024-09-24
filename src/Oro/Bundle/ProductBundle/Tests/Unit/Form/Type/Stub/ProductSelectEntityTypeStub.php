<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Form\Type\Stub;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Component\Testing\Unit\Form\Type\Stub\EntityTypeStub;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProductSelectEntityTypeStub extends EntityTypeStub
{
    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);
        $resolver->setDefaults([
            'autocomplete_alias' => 'oro_product_visibility_limited',
            'grid_name'       => 'products-select-grid',
            'grid_parameters' => [],
            'query_builder'   => null,
            'create_enabled'  => false,
            'class'           => Product::class,
            'data_parameters' => [],
            'choice_label'    => 'sku',
            'configs'         => [
                'placeholder' => null,
            ],
        ]);
    }
}
