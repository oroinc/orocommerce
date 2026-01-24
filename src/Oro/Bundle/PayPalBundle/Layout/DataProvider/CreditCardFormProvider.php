<?php

namespace Oro\Bundle\PayPalBundle\Layout\DataProvider;

use Oro\Bundle\LayoutBundle\Layout\DataProvider\AbstractFormProvider;
use Oro\Bundle\PayPalBundle\Form\Type\CreditCardType;
use Symfony\Component\Form\FormView;

/**
 * Layout data provider for credit card forms in the storefront.
 *
 * Provides form views for credit card data entry during PayPal checkout.
 * It uses {@see CreditCardType} to render credit card forms.
 */
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
