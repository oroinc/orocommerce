<?php

namespace OroB2B\Bundle\TaxBundle\Provider;

use OroB2B\Bundle\PricingBundle\SubtotalProcessor\Model\Subtotal;
use OroB2B\Bundle\PricingBundle\SubtotalProcessor\Model\SubtotalProviderInterface;
use OroB2B\Bundle\TaxBundle\Exception\TaxationDisabledException;
use OroB2B\Bundle\TaxBundle\Manager\TaxManager;

class TaxSubtotalProvider implements SubtotalProviderInterface
{
    const TYPE = 'tax';
    const NAME = 'orob2b_tax.subtotal_tax';

    /**
     * @var TaxManager
     */
    protected $taxManager;

    /**
     * @var TaxationSettingsProvider
     */
    protected $taxationSettingsProvider;

    /**
     * @param TaxManager $taxManager
     * @param TaxationSettingsProvider $taxationSettingsProvider
     */
    public function __construct(
        TaxManager $taxManager,
        TaxationSettingsProvider $taxationSettingsProvider
    ) {
        $this->taxManager = $taxManager;
        $this->taxationSettingsProvider = $taxationSettingsProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function getSubtotal($order)
    {
        $subtotal = new Subtotal();

        $subtotal->setType(self::TYPE);
        $label = 'orob2b.tax.subtotals.' . self::TYPE;
        $subtotal->setLabel($label);

        try {
            $tax = $this->taxManager->loadTax($order);

            $subtotal->setAmount($tax->getTotal()->getTaxAmount());
            $subtotal->setCurrency($tax->getTotal()->getCurrency());
            $subtotal->setVisible(true);
        } catch (TaxationDisabledException $e) {
            $subtotal->setVisible(false);
        }

        return $subtotal;
    }

    /** {@inheritdoc} */
    public function isSupported($entity)
    {
        return $this->taxationSettingsProvider->isEnabled();
    }
}
