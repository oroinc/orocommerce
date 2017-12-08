<?php

namespace Oro\Bundle\SaleBundle\Provider;

use Doctrine\Common\Persistence\ManagerRegistry;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Entity\Repository\CheckoutRepository;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\SaleBundle\Entity\Quote;
use Oro\Bundle\SaleBundle\Entity\QuoteDemand;
use Oro\Bundle\SaleBundle\Entity\Repository\QuoteDemandRepository;

class QuoteCheckoutProvider
{
    /**
     * @var ManagerRegistry
     */
    protected $managerRegistry;

    /**
     * @param ManagerRegistry $managerRegistry
     */
    public function __construct(ManagerRegistry $managerRegistry)
    {
        $this->managerRegistry = $managerRegistry;
    }

    /**
     * @param Quote $quote
     * @param CustomerUser $customerUser
     * @param $workflowName
     *
     * @return null|Checkout
     */
    public function getCheckoutByQuote(Quote $quote, CustomerUser $customerUser, $workflowName)
    {
        $quoteDemand = $this->getQuoteDemandRepository()->getQuoteDemandByQuote($quote, $customerUser);
        if (!$quoteDemand) {
            return null;
        }

        return $this->getCheckoutRepository()->findCheckoutByCustomerUserAndSourceCriteria(
            $customerUser,
            ['quoteDemand' => $quoteDemand],
            $workflowName
        );
    }

    /**
     * @return QuoteDemandRepository
     */
    protected function getQuoteDemandRepository()
    {
        return $this->managerRegistry
            ->getManagerForClass(QuoteDemand::class)
            ->getRepository(QuoteDemand::class);
    }

    /**
     * @return CheckoutRepository
     */
    protected function getCheckoutRepository()
    {
        return $this->managerRegistry
            ->getManagerForClass(Checkout::class)
            ->getRepository(Checkout::class);
    }
}
