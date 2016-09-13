<?php

namespace Oro\Bundle\PayPalBundle\PayPal\Payflow\ExpressCheckout\Option;

use Oro\Bundle\PayPalBundle\PayPal\Payflow\Option;
use Oro\Bundle\PayPalBundle\PayPal\Payflow\Option\OptionInterface;
use Oro\Bundle\PayPalBundle\PayPal\Payflow\Option\OptionsResolver;
use Oro\Bundle\PayPalBundle\PayPal\Payflow\ExpressCheckout\Option as ECOption;

class ActionDependent implements OptionInterface
{
    /**
     * @var array
     */
    protected $options;

    /**
     * @param array $options
     */
    public function __construct(array $options)
    {
        $this->options = $options;
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOption(OptionsResolver $resolver)
    {
        if (isset($this->options[ECOption\Action::ACTION])) {
            $this->configureActionRequiredOptions($this->options, $resolver);
        } else {
            $this->configureNoActionRequiredOptions($resolver);
        }
    }

    /**
     * @param array $options
     * @param OptionsResolver $resolver
     */
    protected function configureActionRequiredOptions(array $options, OptionsResolver $resolver)
    {
        $action = $options[ECOption\Action::ACTION];

        switch ($action) {
            case ECOption\Action::SET_EC:
                $this->addOptions($resolver, [
                    new ECOption\Token(false),
                    new ECOption\PaymentType(),
                    new ECOption\ShippingAddressOverride(),
                    new Option\ReturnUrl(),
                    new Option\CancelUrl(),
                    new Option\Amount(),
                    new Option\LineItems($this->getLineItemCount($options)),
                    new Option\Currency(),
                    new Option\ShippingAddress(),
                    new Option\Invoice(),
                ]);
                break;
            case ECOption\Action::GET_EC_DETAILS:
                $this->addOptions($resolver, [
                    new ECOption\Token(),
                ]);
                break;
            case ECOption\Action::DO_EC:
                $this->addOptions($resolver, [
                    new ECOption\Token(),
                    new ECOption\Payer(),
                    new ECOption\PaymentType(),
                    new Option\Amount(),
                    new Option\LineItems($this->getLineItemCount($options)),
                    new Option\ShippingAddress(),
                    new Option\Invoice(),
                ]);
                break;
        }
    }

    /**
     * @param OptionsResolver $resolver
     */
    private function configureNoActionRequiredOptions(OptionsResolver $resolver)
    {
        $this->addOptions($resolver, [
            new Option\OriginalTransaction(),
            new Option\Amount(false)
        ]);
    }

    /**
     * @param OptionsResolver $resolver
     * @param array $options
     */
    protected function addOptions(OptionsResolver $resolver, array $options)
    {
        /** @var OptionInterface $option */
        foreach ($options as $option) {
            $option->configureOption($resolver);
        }
    }

    /**
     * @param array $options
     * @return int
     */
    protected function getLineItemCount(array $options)
    {
        $nameKey = rtrim(Option\LineItems::NAME, '%d');

        $count = 0;
        foreach ($options as $key => $value) {
            $count += strpos($key, $nameKey) === 0 ? 1 : 0;
        }

        return $count;
    }
}
