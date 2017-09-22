<?php

namespace Oro\Bundle\CheckoutBundle\Model;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutSubtotal;
use Oro\Bundle\CheckoutBundle\Entity\Repository\CheckoutRepository;
use Oro\Bundle\CheckoutBundle\Provider\CheckoutSubtotalProvider;
use Oro\Bundle\PricingBundle\Manager\UserCurrencyManager;

class CheckoutSubtotalUpdater
{
    const BATCH_COUNT = 50;

    /** @var ManagerRegistry */
    protected $registry;

    /** @var CheckoutSubtotalProvider */
    protected $subtotalProvider;

    /** @var UserCurrencyManager */
    protected $currencyManager;

    /**
     * @param ManagerRegistry $managerRegistry
     * @param CheckoutSubtotalProvider $subtotalProvider
     * @param UserCurrencyManager $currencyManager
     */
    public function __construct(
        ManagerRegistry $managerRegistry,
        CheckoutSubtotalProvider $subtotalProvider,
        UserCurrencyManager $currencyManager
    ) {
        $this->registry = $managerRegistry;
        $this->subtotalProvider = $subtotalProvider;
        $this->currencyManager = $currencyManager;
    }

    public function recalculateInvalidSubtotals()
    {
        $entityManager = $this->getEntityManager();
        /** @var CheckoutRepository $repository */
        $repository = $entityManager->getRepository(Checkout::class);
        $checkouts = $repository->findWithInvalidSubtotals();
        $cnt = 0;
        foreach ($checkouts as $checkout) {
            $cnt++;
            $this->processCheckoutSubtotals($checkout);
            if ($cnt % self::BATCH_COUNT === 0) {
                $entityManager->flush();
                $cnt = 0;
            }
        }

        if ($cnt) {
            $entityManager->flush();
        }
    }

    /**
     * @param Checkout $checkout
     * @param bool $doFlush
     */
    public function recalculateCheckoutSubtotals(Checkout $checkout, $doFlush = false)
    {
        $this->processCheckoutSubtotals($checkout);

        if ($doFlush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * @param Checkout $checkout
     */
    protected function processCheckoutSubtotals(Checkout $checkout)
    {
        $checkoutSubtotals = $checkout->getSubtotals();
        $enabledCurrencies = $this->currencyManager->getAvailableCurrencies();
        $processedCurrencies = [];
        foreach ($checkoutSubtotals as $checkoutSubtotal) {
            $currency = $checkoutSubtotal->getCurrency();
            $subtotal = $this->subtotalProvider->getSubtotalByCurrency($checkout, $currency);
            $checkoutSubtotal->setSubtotal($subtotal)
                ->setValid(true);
            $processedCurrencies[] = $currency;
        }

        $entityManager = $this->getEntityManager();
        $notProcessedCurrencies = array_diff($enabledCurrencies, $processedCurrencies);
        foreach ($notProcessedCurrencies as $currency) {
            $checkoutSubtotal = new CheckoutSubtotal($checkout, $currency);
            $subtotal = $this->subtotalProvider->getSubtotalByCurrency($checkout, $currency);

            $checkoutSubtotal->setSubtotal($subtotal)
                ->setValid(true);
            $entityManager->persist($checkoutSubtotal);
        }
    }

    /**
     * @return ObjectManager
     */
    protected function getEntityManager()
    {
        return $this->registry->getManagerForClass(CheckoutSubtotal::class);
    }
}
