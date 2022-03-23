<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\Datagrid\Extension\MassAction;

use Oro\Bundle\DataGridBundle\Extension\MassAction\MassActionHandlerArgs;
use Oro\Bundle\DataGridBundle\Extension\MassAction\MassActionResponse;
use Oro\Bundle\PromotionBundle\Datagrid\Extension\MassAction\CouponUnassignActionHandler;
use Symfony\Contracts\Translation\TranslatorInterface;

class CouponUnassignActionHandlerTest extends AbstractCouponMassActionHandlerTest
{
    /** @var TranslatorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $translator;

    protected function setUp(): void
    {
        $this->translator = $this->createMock(TranslatorInterface::class);

        parent::setUp();
    }

    /**
     * {@inheritdoc}
     */
    protected function createHandler()
    {
        $this->handler = new CouponUnassignActionHandler(
            $this->doctrineHelper,
            $this->aclHelper
        );
        $this->handler->setTranslator($this->translator);
    }

    /**
     * {@inheritdoc}
     */
    protected function assertExecuteCalled(
        array $coupons,
        MassActionHandlerArgs|\PHPUnit\Framework\MockObject\MockObject $args
    ): void {
        foreach ($coupons as $coupon) {
            $coupon->expects($this->once())
                ->method('setPromotion')
                ->with(null);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function assertGetResponseCalled(int $entitiesCount): MassActionResponse
    {
        $translatedMessage = $entitiesCount . ' processed';
        $this->translator->expects($this->once())
            ->method('trans')
            ->with(
                'oro.promotion.mass_action.unassign.success_message',
                ['%count%' => $entitiesCount]
            )
            ->willReturn($translatedMessage);

        return new MassActionResponse(true, $translatedMessage, ['count' => $entitiesCount]);
    }
}
