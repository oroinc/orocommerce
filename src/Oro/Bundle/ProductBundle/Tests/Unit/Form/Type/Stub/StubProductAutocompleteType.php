<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Form\Type\Stub;

use Oro\Bundle\ProductBundle\Form\Type\ProductAutocompleteType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class StubProductAutocompleteType extends AbstractType
{
    #[\Override]
    public function getBlockPrefix(): string
    {
        return ProductAutocompleteType::NAME;
    }

    #[\Override]
    public function getParent(): ?string
    {
        return TextType::class;
    }
}
