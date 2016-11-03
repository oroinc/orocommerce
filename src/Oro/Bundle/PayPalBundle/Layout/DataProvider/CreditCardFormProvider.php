<?php

namespace Oro\Bundle\PayPalBundle\Layout\DataProvider;

use Symfony\Component\Form\FormInterface;

use Oro\Bundle\LayoutBundle\Layout\DataProvider\AbstractFormProvider;
use Oro\Bundle\PayPalBundle\Form\Type\CreditCardType;

class CreditCardFormProvider extends AbstractFormProvider
{
    /**
     * @return FormInterface
     */
    public function getCreditCardFormView()
    {
        return $this->getFormView(CreditCardType::NAME, null, []);
    }
}
