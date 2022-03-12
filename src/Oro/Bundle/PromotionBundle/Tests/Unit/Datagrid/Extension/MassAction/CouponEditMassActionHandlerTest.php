<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\Datagrid\Extension\MassAction;

use Oro\Bundle\DataGridBundle\Extension\MassAction\MassActionHandlerArgs;
use Oro\Bundle\DataGridBundle\Extension\MassAction\MassActionResponse;
use Oro\Bundle\PromotionBundle\Datagrid\Extension\MassAction\CouponEditMassActionHandler;
use Oro\Bundle\PromotionBundle\Entity\Coupon;
use Oro\Bundle\PromotionBundle\Form\Type\BaseCouponType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class CouponEditMassActionHandlerTest extends AbstractCouponMassActionHandlerTest
{
    /** @var TranslatorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $translator;

    /** @var FormFactoryInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $formFactory;

    protected function setUp(): void
    {
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->formFactory = $this->createMock(FormFactoryInterface::class);

        parent::setUp();
    }

    /**
     * {@inheritdoc}
     */
    protected function createHandler()
    {
        $this->handler = new CouponEditMassActionHandler(
            $this->doctrineHelper,
            $this->aclHelper
        );
        $this->handler->setTranslator($this->translator);
        $this->handler->setFormFactory($this->formFactory);
    }

    /**
     * {@inheritdoc}
     */
    protected function assertExecuteCalled(
        array $coupons,
        MassActionHandlerArgs|\PHPUnit\Framework\MockObject\MockObject $args
    ): void {
        $formData = ['test' => true];
        $requestData = [BaseCouponType::NAME => $formData];
        $args->expects($this->once())
            ->method('getData')
            ->willReturn($requestData);

        $form = $this->createMock(FormInterface::class);
        $form->expects($this->any())
            ->method('submit')
            ->with($formData);

        $this->formFactory->expects($this->exactly(count($coupons)))
            ->method('create')
            ->with(BaseCouponType::class, $this->isInstanceOf(Coupon::class))
            ->willReturn($form);
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
                'oro.grid.mass_action.edit.success_message',
                ['%count%' => $entitiesCount]
            )
            ->willReturn($translatedMessage);

        return new MassActionResponse(true, $translatedMessage, ['count' => $entitiesCount]);
    }
}
