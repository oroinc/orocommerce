<?php

namespace Oro\Bundle\CheckoutBundle\WorkflowState\Mapper;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\PricingBundle\SubtotalProcessor\TotalProcessorProvider;

/**
 * Maps total amount changes for checkout state diff tracking.
 *
 * Tracks changes to the total amount and currency in a checkout, enabling detection of
 * price modifications during the checkout workflow.
 */
class TotalAmountDiffMapper implements CheckoutStateDiffMapperInterface
{
    public const DATA_NAME = 'total_amount';

    /**
     * @var TotalProcessorProvider
     */
    protected $totalProcessorProvider;

    public function __construct(TotalProcessorProvider $totalProcessorProvider)
    {
        $this->totalProcessorProvider = $totalProcessorProvider;
    }

    #[\Override]
    public function isEntitySupported($entity)
    {
        return is_object($entity) && $entity instanceof Checkout;
    }

    #[\Override]
    public function getName()
    {
        return self::DATA_NAME;
    }

    /**
     * @param Checkout $checkout
     * @return array
     */
    #[\Override]
    public function getCurrentState($checkout)
    {
        $total = $this->totalProcessorProvider->getTotal($checkout);

        return [
            'amount' => $total->getAmount(),
            'currency' => $total->getCurrency(),
        ];
    }

    /**
     * @param array $entity
     * @param array $state1
     * @param array $state2
     * @return bool
     */
    #[\Override]
    public function isStatesEqual($entity, $state1, $state2)
    {
        foreach (['amount', 'currency'] as $field) {
            $state1Value = $this->getValue($state1, $field);
            $state2Value = $this->getValue($state2, $field);

            if ($state1Value === null || $state2Value === null) {
                return false;
            }

            if ($state1Value !== $state2Value) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param array $state
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    protected function getValue($state, $key, $default = null)
    {
        return array_key_exists($key, $state) ? $state[$key] : $default;
    }
}
