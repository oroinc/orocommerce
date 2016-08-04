<?php

namespace Oro\Bundle\PayPalBundle\Layout\DataProvider;

use Oro\Bundle\LayoutBundle\Layout\Form\FormAccessor;
use Oro\Bundle\PayPalBundle\Form\Type\CreditCardType;

use Oro\Component\Layout\DataProvider\AbstractFormProvider;

class CreditCardFormProvider extends AbstractFormProvider
{
    /**
     * @return FormAccessor
     */
    public function getCreditCardForm()
    {
        return $this->getFormAccessor(CreditCardType::NAME);
    }
}
