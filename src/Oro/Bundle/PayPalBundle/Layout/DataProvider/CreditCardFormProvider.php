<?php

namespace Oro\Bundle\PayPalBundle\Layout\DataProvider;

use Oro\Bundle\LayoutBundle\Layout\DataProvider\AbstractFormProvider;
use Oro\Bundle\PayPalBundle\Form\Type\CreditCardType;
use Symfony\Component\Form\FormView;

class CreditCardFormProvider extends AbstractFormProvider
{
    /**
     * @return FormView
     */
    public function getCreditCardFormView()
    {
        return $this->getFormView(CreditCardType::class, null, []);
    }
}
