<?php

namespace OroB2B\Bundle\CheckoutBundle\DataProvider\Manager;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use OroB2B\Bundle\CheckoutBundle\DataProvider\Converter\CheckoutLineItemsConverter;
use OroB2B\Bundle\CheckoutBundle\Entity\CheckoutInterface;
use OroB2B\Bundle\OrderBundle\Entity\OrderLineItem;
use OroB2B\Bundle\PricingBundle\Provider\UserCurrencyProvider;
use OroB2B\Component\Checkout\DataProvider\CheckoutDataProviderInterface;

class CheckoutLineItemsManager
{
    /**
     * @var CheckoutDataProviderInterface[]
     */
    protected $providers = [];

    /**
     * @var CheckoutLineItemsConverter
     */
    protected $checkoutLineItemsConverter;

    /**
     * @var UserCurrencyProvider
     */
    protected $userCurrencyProvider;

    /**
     * @param CheckoutLineItemsConverter $checkoutLineItemsConverter
     * @param UserCurrencyProvider $userCurrencyProvider
     */
    public function __construct(
        CheckoutLineItemsConverter $checkoutLineItemsConverter,
        UserCurrencyProvider $userCurrencyProvider
    ) {
        $this->checkoutLineItemsConverter = $checkoutLineItemsConverter;
        $this->userCurrencyProvider = $userCurrencyProvider;
    }

    /**
     * @param CheckoutDataProviderInterface $provider
     */
    public function addProvider(CheckoutDataProviderInterface $provider)
    {
        $this->providers[] = $provider;
    }

    /**
     * @param CheckoutInterface $checkout
     * @param bool $disablePriceFilter
     * @return Collection|OrderLineItem[]
     */
    public function getData(CheckoutInterface $checkout, $disablePriceFilter = false)
    {
        $entity = $checkout->getSourceEntity();
        $currency = $this->userCurrencyProvider->getUserCurrency();
        foreach ($this->providers as $provider) {
            if ($provider->isEntitySupported($entity)) {
                $lineItems = $this->checkoutLineItemsConverter->convert($provider->getData($entity));
                if (!$disablePriceFilter) {
                    $lineItems = $lineItems->filter(function (OrderLineItem $lineItem) use ($currency) {
                        if ($lineItem->getPrice() && $lineItem->getPrice()->getCurrency() === $currency) {
                            return $lineItem->getPrice();
                        }
                        return null;
                    });
                }
                return $lineItems;
            }
        }

        return new ArrayCollection();
    }
}
