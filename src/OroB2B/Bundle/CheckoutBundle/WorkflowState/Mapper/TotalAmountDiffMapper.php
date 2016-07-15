<?php

namespace OroB2B\Bundle\CheckoutBundle\WorkflowState\Mapper;

use OroB2B\Bundle\CheckoutBundle\Entity\Checkout;
use OroB2B\Bundle\PricingBundle\SubtotalProcessor\TotalProcessorProvider;

class TotalAmountDiffMapper implements CheckoutStateDiffMapperInterface
{
    const DATA_NAME = 'totalAmount';

    /**
     * @var TotalProcessorProvider
     */
    protected $totalProcessorProvider;

    /**
     * @param TotalProcessorProvider $totalProcessorProvider
     */
    public function __construct(TotalProcessorProvider $totalProcessorProvider)
    {
        $this->totalProcessorProvider = $totalProcessorProvider;
    }

    /**
     * @return int
     */
    public function getPriority()
    {
        return 50;
    }

    /**
     * @param object $entity
     * @return boolean
     */
    public function isEntitySupported($entity)
    {
        return $entity instanceof Checkout;
    }

    /**
     * @param Checkout $checkout
     * @return array
     */
    public function getCurrentState($checkout)
    {
        $total = $this->totalProcessorProvider->getTotal($checkout);

        return [
            self::DATA_NAME => [
                'amount' => $total->getAmount(),
                'currency' => $total->getCurrency(),
            ],
        ];
    }

    /**
     * @param Checkout $checkout
     * @param array $savedState
     * @return bool
     */
    public function compareStates($checkout, array $savedState)
    {
        $total = $this->totalProcessorProvider->getTotal($checkout);

        return
            isset($savedState[self::DATA_NAME]) &&
            isset($savedState[self::DATA_NAME]['amount']) &&
            isset($savedState[self::DATA_NAME]['currency']) &&
            $savedState[self::DATA_NAME]['amount'] === $total->getAmount() &&
            $savedState[self::DATA_NAME]['currency'] === $total->getCurrency();
    }
}
