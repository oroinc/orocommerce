<?php

namespace Oro\Bundle\PromotionBundle\Datagrid\Extension\MassAction;

use Oro\Bundle\DataGridBundle\Extension\MassAction\MassActionHandlerArgs;
use Oro\Bundle\DataGridBundle\Extension\MassAction\MassActionResponse;
use Oro\Bundle\PromotionBundle\Entity\Coupon;
use Symfony\Contracts\Translation\TranslatorInterface;

class CouponUnassignActionHandler extends AbstractCouponMassActionHandler
{
    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @param mixed $translator
     */
    public function setTranslator(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(Coupon $coupon, MassActionHandlerArgs $args)
    {
        $coupon->setPromotion(null);
    }

    /**
     * {@inheritdoc}
     */
    protected function getResponse($entitiesCount)
    {
        $successful = $entitiesCount > 0;
        $options = ['count' => $entitiesCount];

        return new MassActionResponse(
            $successful,
            $this->translator->trans(
                'oro.promotion.mass_action.unassign.success_message',
                ['%count%' => $entitiesCount]
            ),
            $options
        );
    }
}
