<?php

namespace Oro\Bundle\CheckoutBundle\Model;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutSubtotal;
use Oro\Bundle\CheckoutBundle\Entity\Repository\CheckoutRepository;
use Oro\Bundle\CheckoutBundle\Provider\CheckoutSubtotalProvider;
use Oro\Bundle\PricingBundle\Manager\UserCurrencyManager;

/**
 * Recalculate subtotals of checkout for list of enabled system currencies
 */
class CheckoutSubtotalUpdater
{
    const BATCH_COUNT = 50;

    /** @var ManagerRegistry */
    protected $registry;

    /** @var CheckoutSubtotalProvider */
    protected $subtotalProvider;

    /** @var UserCurrencyManager */
    protected $currencyManager;

    /** @var int */
    private $batchSize = self::BATCH_COUNT;

    public function __construct(
        ManagerRegistry $managerRegistry,
        CheckoutSubtotalProvider $subtotalProvider,
        UserCurrencyManager $currencyManager
    ) {
        $this->registry = $managerRegistry;
        $this->subtotalProvider = $subtotalProvider;
        $this->currencyManager = $currencyManager;
    }

    public function setBatchSize(int $batchSize)
    {
        $this->batchSize = $batchSize;
    }

    public function recalculateInvalidSubtotals()
    {
        $entityManager = $this->getEntityManager();
        /** @var CheckoutRepository $repository */
        $repository = $entityManager->getRepository(Checkout::class);
        $checkouts = $repository->findWithInvalidSubtotals();
        $enabledCurrencies = $this->currencyManager->getAvailableCurrencies();

        $cnt = 0;
        foreach ($checkouts as $checkout) {
            $cnt++;
            $this->processCheckoutSubtotals($checkout, $enabledCurrencies);
            if ($cnt % $this->batchSize === 0) {
                $entityManager->flush();
                $entityManager->clear();
                $cnt = 0;
            }
        }

        if ($cnt) {
            $entityManager->flush();
            $entityManager->clear();
        }
    }

    /**
     * @param Checkout $checkout
     * @param bool $doFlush
     */
    public function recalculateCheckoutSubtotals(Checkout $checkout, $doFlush = false)
    {
        $this->processCheckoutSubtotals($checkout, $this->currencyManager->getAvailableCurrencies());

        if ($doFlush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Prepare subtotals collection for all enabled system currencies. Old subtotals of checkout will be updated.
     * If for some currencies there are no ssubtotals they will be created.
     */
    protected function processCheckoutSubtotals(Checkout $checkout, array $enabledCurrencies)
    {
        $checkoutSubtotals = $checkout->getSubtotals();

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
