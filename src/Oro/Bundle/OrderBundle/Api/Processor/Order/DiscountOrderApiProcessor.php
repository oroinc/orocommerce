<?php

namespace Oro\Bundle\OrderBundle\Api\Processor\Order;

use Oro\Bundle\OrderBundle\Entity\OrderDiscount;
use Oro\Bundle\OrderBundle\Total\TotalHelper;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Processor\FormContext;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

class DiscountOrderApiProcessor implements ProcessorInterface
{
    /**
     * @var TotalHelper
     */
    private $totalHelper;

    /**
     * @param TotalHelper $totalHelper
     */
    public function __construct(TotalHelper $totalHelper)
    {
        $this->totalHelper = $totalHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        if (!$context instanceof FormContext) {
            return;
        }

        $context
            ->getFormBuilder()
            ->addEventListener(
                FormEvents::SUBMIT,
                function (FormEvent $event) {
                    /** @var OrderDiscount $data */
                    $data = $event->getData();

                    $data->getOrder()->addDiscount($data);
                    $this->totalHelper->fillDiscounts($data->getOrder());
                }
            );
    }
}
