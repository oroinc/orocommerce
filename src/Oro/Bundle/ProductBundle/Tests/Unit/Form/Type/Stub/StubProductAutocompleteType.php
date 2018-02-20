<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Form\Type\Stub;

use Oro\Bundle\ProductBundle\Form\Type\ProductAutocompleteType;
use Symfony\Component\Form\AbstractType;

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
