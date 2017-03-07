<?php

namespace Oro\Bundle\OrderBundle\Api\Processor\Order;

use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\OrderBundle\Total\TotalHelper;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Processor\FormContext;
use Oro\Bundle\OrderBundle\Entity\Order;

class TotalsOrderApiProcessor implements ProcessorInterface
{
    /**
     * @var TotalHelper
     */
    private $totalHelper;

    /**
     * @var DoctrineHelper
     */
    private $doctrineHelper;

    /**
     * @param TotalHelper    $totalHelper
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(TotalHelper $totalHelper, DoctrineHelper $doctrineHelper)
    {
        $this->totalHelper = $totalHelper;
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        if (!$context instanceof FormContext) {
            return;
        }

        $requestData = $context->getRequestData();
        $order = $context->getResult();

        if (!$requestData || !$order instanceof Order) {
            return;
        }

        $this->totalHelper->fillSubtotals($order);
        $this->totalHelper->fillTotal($order);
        $this->totalHelper->fillDiscounts($order);

        $manager = $this->doctrineHelper->getEntityManager($order);

        $manager->persist($order);
        $manager->flush();
    }
}
