<?php

namespace Oro\Bundle\ApruveBundle\Provider;

use Oro\Bundle\PaymentBundle\Context\PaymentContextInterface;
use Oro\Bundle\TaxBundle\Exception\TaxationDisabledException;
use Oro\Bundle\TaxBundle\Manager\TaxManager;
use Oro\Bundle\TaxBundle\Mapper\UnmappableArgumentException;

class TaxAmountProvider implements TaxAmountProviderInterface
{
    /**
     * @var TaxManager
     */
    private $taxManager;

    /**.
     * @param TaxManager $taxManager
     */
    public function __construct(TaxManager $taxManager)
    {
        $this->taxManager = $taxManager;
    }

    /**
     * {@inheritdoc}
     */
    public function getTaxAmount(PaymentContextInterface $paymentContext)
    {
        try {
            $result = $this->taxManager->loadTax($paymentContext->getSourceEntity());
            $taxAmount = $result->getTotal()->getTaxAmount();
        } catch (TaxationDisabledException $ex) {
            $taxAmount = 0;
        } catch (UnmappableArgumentException $ex) {
            // TODO@webevt: log exception

            // There are no tax mapper for given source entity.
            $taxAmount = 0;
        } catch (\InvalidArgumentException $ex) {
            // TODO@webevt: log exception
            $taxAmount = 0;
        }

        if (abs((float)$taxAmount) <= 1e-6) {
            $taxAmount = 0;
        }

        return (float) $taxAmount;
    }
}
