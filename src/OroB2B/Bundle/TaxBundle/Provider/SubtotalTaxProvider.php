<?php

namespace OroB2B\Bundle\TaxBundle\Provider;

use Symfony\Component\Translation\TranslatorInterface;

use OroB2B\Bundle\TaxBundle\Exception\TaxationDisabledException;
use OroB2B\Bundle\TaxBundle\Manager\TaxManager;
use OroB2B\Bundle\OrderBundle\Entity\Order;
use OroB2B\Bundle\OrderBundle\Model\Subtotal;
use OroB2B\Bundle\OrderBundle\SubtotalProcessor\SubtotalProviderInterface;

class SubtotalTaxProvider implements SubtotalProviderInterface
{
    const TYPE = 'tax';
    const NAME = 'orob2b_tax.subtotal_tax';

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @var TaxManager
     */
    protected $taxManager;

    /**
     * @param TranslatorInterface $translator
     */
    public function __construct(TranslatorInterface $translator, TaxManager $taxManager)
    {
        $this->translator = $translator;
        $this->taxManager = $taxManager;
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
    public function getSubtotal(Order $order)
    {
        $subtotal = new Subtotal();

        $subtotal->setType(self::TYPE);
        $label = 'orob2b.tax.subtotals.' . self::TYPE;
        $subtotal->setLabel($this->translator->trans($label));

        try {
            $tax = $this->taxManager->loadTax($order);

            $subtotal->setAmount($tax->getTotal()->getTaxAmount());
            $subtotal->setCurrency($tax->getTotal()->getCurrency());
        } catch (TaxationDisabledException $e) {
            $subtotal->setVisible(false);
        }

        return $subtotal;
    }
}
