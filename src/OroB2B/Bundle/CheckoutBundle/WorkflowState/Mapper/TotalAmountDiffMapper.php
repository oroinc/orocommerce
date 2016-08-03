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
     * @param Checkout $checkout
     * @param array $savedState
     * @return bool
     */
    public function isStateActual($checkout, array $savedState)
    {
        if (!isset($savedState[$this->getName()]) ||
            !isset($savedState[$this->getName()]['amount']) ||
            !isset($savedState[$this->getName()]['currency'])
        ) {
            return true;
        }

        return $savedState[$this->getName()]['amount'] === $this->getCurrentState($checkout)['amount'] &&
            $savedState[$this->getName()]['currency'] === $this->getCurrentState($checkout)['currency'];
    }
}
