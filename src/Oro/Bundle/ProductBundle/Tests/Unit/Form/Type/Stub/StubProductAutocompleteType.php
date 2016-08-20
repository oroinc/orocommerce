<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Form\Type\Stub;

use Symfony\Component\Form\AbstractType;

use Oro\Bundle\ProductBundle\Form\Type\ProductAutocompleteType;

class StubProductAutocompleteType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return ProductAutocompleteType::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return 'text';
    }
}
