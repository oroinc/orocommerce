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
        if (!isset(
            $state1[$this->getName()]['amount'],
            $state2[$this->getName()]['amount'],
            $state1[$this->getName()]['currency'],
            $state2[$this->getName()]['currency']
        )
        ) {
            return true;
        }

        return $state1[$this->getName()]['amount'] === $state2[$this->getName()]['amount'] &&
            $state1[$this->getName()]['currency'] === $state2[$this->getName()]['currency'];
    }
}
