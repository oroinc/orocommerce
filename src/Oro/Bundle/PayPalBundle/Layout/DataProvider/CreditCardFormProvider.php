<?php

namespace Oro\Bundle\PayPalBundle\Layout\DataProvider;

use Oro\Bundle\LayoutBundle\Layout\Form\FormAccessor;

use Oro\Component\Layout\DataProvider\AbstractFormDataProvider;

use Oro\Bundle\PayPalBundle\Form\Type\CreditCardType;

class CreditCardFormProvider extends AbstractFormDataProvider
{
    /**
     * @return FormAccessor
     */
    public function getCreditCardForm()
    {
        return $this->getFormAccessor(CreditCardType::NAME);
    }
}
