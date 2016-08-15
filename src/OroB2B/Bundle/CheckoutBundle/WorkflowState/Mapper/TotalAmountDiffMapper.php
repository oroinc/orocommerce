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
     * {@inheritdoc}
     */
    public function isEntitySupported($entity)
    {
        return is_object($entity) && $entity instanceof Checkout;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::DATA_NAME;
    }

    /**
     * @param Checkout $checkout
     * @return array
     */
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
    public function isStatesEqual($entity, $state1, $state2)
    {
        foreach (['amount', 'currency'] as $field) {
            if ($this->getValue($state1, $field) === null || $this->getValue($state2, $field) === null) {
                return true;
            }
        }

        return $state1['amount'] === $state2['amount'] &&
            $state1['currency'] === $state2['currency'];
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
