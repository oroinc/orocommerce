<?php

namespace Oro\Bundle\PayPalBundle\Layout\DataProvider;

use Symfony\Component\Form\FormInterface;

use Oro\Bundle\LayoutBundle\Layout\DataProvider\AbstractFormProvider;
use Oro\Bundle\PayPalBundle\Form\Type\CreditCardType;

class CreditCardFormProvider extends AbstractFormProvider
{
    /**
     * @param array $options
     *
     * @return FormInterface
     */
    public function getCreditCardForm(array $options = [])
    {
        return $this->getForm(CreditCardType::NAME, null, $options);
    }
}
