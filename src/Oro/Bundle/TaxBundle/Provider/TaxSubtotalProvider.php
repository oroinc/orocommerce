<?php

namespace Oro\Bundle\TaxBundle\Provider;

use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\CacheAwareInterface;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\Subtotal;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\SubtotalProviderInterface;
use Oro\Bundle\TaxBundle\Exception\TaxationDisabledException;
use Oro\Bundle\TaxBundle\Factory\TaxFactory;
use Oro\Bundle\TaxBundle\Model\Result;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Subtotal provider for taxes.
 */
class TaxSubtotalProvider implements SubtotalProviderInterface, CacheAwareInterface
{
    const TYPE = 'tax';
    const SUBTOTAL_ORDER = 500;

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @var TaxProviderRegistry
     */
    protected $taxProviderRegistry;

    /**
     * @var TaxFactory
     */
    protected $taxFactory;

    /**
     * @var TaxationSettingsProvider
     */
    protected $taxationSettingsProvider;

    public function __construct(
        TranslatorInterface $translator,
        TaxProviderRegistry $taxProviderRegistry,
        TaxFactory $taxFactory,
        TaxationSettingsProvider $taxationSettingsProvider
    ) {
        $this->translator = $translator;
        $this->taxProviderRegistry = $taxProviderRegistry;
        $this->taxFactory = $taxFactory;
        $this->taxationSettingsProvider = $taxationSettingsProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function getSubtotal($entity)
    {
        $subtotal = $this->createSubtotal();

        try {
            $tax = $this->getProvider()->getTax($entity);
            $this->fillSubtotal($subtotal, $tax);
        } catch (TaxationDisabledException $e) {
        }

        return $subtotal;
    }

    /**
     * {@inheritdoc}
     */
    public function getCachedSubtotal($entity)
    {
        $subtotal = $this->createSubtotal();
        try {
            $tax = $this->getProvider()->loadTax($entity);
            $this->fillSubtotal($subtotal, $tax);
        } catch (TaxationDisabledException $e) {
        }

        return $subtotal;
    }

    /**
     * @return Subtotal
     */
    protected function createSubtotal()
    {
        $subtotal = new Subtotal();

        $subtotal->setType(self::TYPE);
        $label = 'oro.tax.subtotals.' . self::TYPE;
        $subtotal->setLabel($this->translator->trans($label));
        $subtotal->setVisible(false);
        $subtotal->setSortOrder(self::SUBTOTAL_ORDER);

        return $subtotal;
    }

    /**
     * @param Subtotal $subtotal
     * @param Result $tax
     * @return Subtotal
     */
    protected function fillSubtotal(Subtotal $subtotal, Result $tax)
    {
        $subtotal->setAmount($tax->getTotal()->getTaxAmount());
        $subtotal->setCurrency($tax->getTotal()->getCurrency());
        $subtotal->setVisible((bool)$tax->getTotal()->getTaxAmount());

        if ($this->taxationSettingsProvider->isProductPricesIncludeTax()) {
            $subtotal->setOperation(Subtotal::OPERATION_IGNORE);
        }

        $subtotal->setData($tax->getArrayCopy());

        return $subtotal;
    }

    /** {@inheritdoc} */
    public function isSupported($entity)
    {
        return $this->taxFactory->supports($entity);
    }

    /**
     * {@inheritdoc}
     */
    public function supportsCachedSubtotal($entity)
    {
        return $this->taxFactory->supports($entity);
    }

    /**
     * @return TaxProviderInterface
     */
    private function getProvider()
    {
        return $this->taxProviderRegistry->getEnabledProvider();
    }
}
