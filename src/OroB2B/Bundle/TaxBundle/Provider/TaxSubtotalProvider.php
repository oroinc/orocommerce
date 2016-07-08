<?php

namespace OroB2B\Bundle\TaxBundle\Provider;

use Symfony\Component\Translation\TranslatorInterface;

use OroB2B\Bundle\PricingBundle\SubtotalProcessor\Model\CacheAwareInterface;
use OroB2B\Bundle\PricingBundle\SubtotalProcessor\Model\Subtotal;
use OroB2B\Bundle\PricingBundle\SubtotalProcessor\Model\SubtotalProviderInterface;
use OroB2B\Bundle\TaxBundle\Exception\TaxationDisabledException;
use OroB2B\Bundle\TaxBundle\Factory\TaxFactory;
use OroB2B\Bundle\TaxBundle\Manager\TaxManager;
use OroB2B\Bundle\TaxBundle\Model\Result;

class TaxSubtotalProvider implements SubtotalProviderInterface, CacheAwareInterface
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
    public function getSubtotal($entity)
    {
        $subtotal = $this->createSubtotal();

        try {
            $tax = $this->taxManager->getTax($entity);
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
            $tax = $this->taxManager->loadTax($entity);
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
        $label = 'orob2b.tax.subtotals.' . self::TYPE;
        $subtotal->setLabel($this->translator->trans($label));
        $subtotal->setVisible(false);

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
        $subtotal->setVisible(true);
        $subtotal->setData($tax->getArrayCopy());

        return $subtotal;
    }

    /** {@inheritdoc} */
    public function isSupported($entity)
    {
        return $this->taxFactory->supports($entity);
    }
}
