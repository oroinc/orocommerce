<?php

namespace Oro\Bundle\ApruveBundle\Apruve\Model;

use Oro\Bundle\ApruveBundle\Apruve\Helper\AmountNormalizer;
use Oro\Bundle\ApruveBundle\Apruve\Provider\SupportedCurrenciesProviderInterface;
use Oro\Bundle\CurrencyBundle\Entity\Price;

class AbstractApruveEntityFactory
{
    /**
     * @var SupportedCurrenciesProviderInterface
     */
    protected $supportedCurrenciesProvider;

    /**
     * @param SupportedCurrenciesProviderInterface $supportedCurrenciesProvider
     */
    public function __construct(SupportedCurrenciesProviderInterface $supportedCurrenciesProvider)
    {
        $this->supportedCurrenciesProvider = $supportedCurrenciesProvider;
    }

    /**
     * @param mixed $amount
     *
     * @return int
     */
    protected function normalizeAmount($amount)
    {
        return AmountNormalizer::normalize($amount);
    }

    /**
     * @param Price|null $price
     *
     * @return int
     */
    protected function getAmountFromPrice(Price $price = null)
    {
        return $price ? $this->normalizeAmount($price->getValue()) : 0;
    }

    /**
     * @param string $currency
     *
     * @return string
     *
     * @throws \InvalidArgumentException
     */
    protected function getCurrency($currency)
    {
        if (!$this->supportedCurrenciesProvider->isSupported($currency)) {
            $msg = sprintf('Currency %s is not supported', $currency);
            throw new \InvalidArgumentException($msg);
        }

        return $currency;
    }
}
