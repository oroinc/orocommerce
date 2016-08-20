<?php

namespace Oro\Bundle\TaxBundle\Provider;

use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\CacheAwareInterface;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\Subtotal;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\SubtotalProviderInterface;
use Oro\Bundle\TaxBundle\Exception\TaxationDisabledException;
use Oro\Bundle\TaxBundle\Factory\TaxFactory;
use Oro\Bundle\TaxBundle\Manager\TaxManager;
use Oro\Bundle\TaxBundle\Model\Result;

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
        $label = 'oro.tax.subtotals.' . self::TYPE;
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
