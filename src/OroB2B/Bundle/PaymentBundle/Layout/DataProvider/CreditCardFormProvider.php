<?php

namespace OroB2B\Bundle\PaymentBundle\Layout\DataProvider;

use Oro\Bundle\LayoutBundle\Layout\Form\FormAccessor;

use Oro\Component\Layout\DataProvider\AbstractFormDataProvider;

use OroB2B\Bundle\PaymentBundle\Form\Type\CreditCardType;

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
