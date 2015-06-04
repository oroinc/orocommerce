<?php

namespace OroB2B\Bundle\ProductBundle\Tests\Unit\Form\Type\Stub;

use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Oro\Component\Testing\Unit\Form\Type\Stub\EntityType as StubEntityType;

class StubCurrencySelectionType extends StubEntityType
{
    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults([
            'compact' => false,
            'currencies_list' => null,
            'choice_list' => $this->choiceList,
            'full_currency_list' => null
        ]);
    }
}
