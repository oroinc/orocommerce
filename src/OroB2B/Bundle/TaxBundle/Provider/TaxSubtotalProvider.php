<?php

namespace OroB2B\Bundle\TaxBundle\Provider;

use Symfony\Component\Translation\TranslatorInterface;

use OroB2B\Bundle\PricingBundle\SubtotalProcessor\Model\Subtotal;
use OroB2B\Bundle\PricingBundle\SubtotalProcessor\Model\SubtotalProviderInterface;
use OroB2B\Bundle\TaxBundle\Exception\TaxationDisabledException;
use OroB2B\Bundle\TaxBundle\Factory\TaxFactory;
use OroB2B\Bundle\TaxBundle\Manager\TaxManager;

class TaxSubtotalProvider implements SubtotalProviderInterface
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
     * @var TaxFactory
     */
    protected $taxFactory;

    /**
     * @param TranslatorInterface $translator
     * @param TaxManager $taxManager
     * @param TaxFactory $taxFactory
     */
    public function __construct(TranslatorInterface $translator, TaxManager $taxManager, TaxFactory $taxFactory)
    {
        $this->translator = $translator;
        $this->taxManager = $taxManager;
        $this->taxFactory = $taxFactory;
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
        $subtotal->setLabel($this->translator->trans($label));

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
        return $this->taxFactory->supports($entity);
    }
}
