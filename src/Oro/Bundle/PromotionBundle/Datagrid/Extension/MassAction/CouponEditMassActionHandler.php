<?php

namespace Oro\Bundle\PromotionBundle\Datagrid\Extension\MassAction;

use Oro\Bundle\DataGridBundle\Exception\LogicException;
use Oro\Bundle\DataGridBundle\Extension\MassAction\MassActionHandlerArgs;
use Oro\Bundle\DataGridBundle\Extension\MassAction\MassActionResponse;
use Oro\Bundle\DataGridBundle\Extension\MassAction\MassActionResponseInterface;
use Oro\Bundle\PromotionBundle\Entity\Coupon;
use Oro\Bundle\PromotionBundle\Form\Type\BaseCouponType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * The edit mass action handler for Coupon entity.
 */
class CouponEditMassActionHandler extends AbstractCouponMassActionHandler
{
    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var FormFactoryInterface
     */
    private $formFactory;

    /**
     * @var array
     */
    private $formData;

    public function setTranslator(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    public function setFormFactory(FormFactoryInterface $formFactory)
    {
        $this->formFactory = $formFactory;
    }

    #[\Override]
    public function handle(MassActionHandlerArgs $args): MassActionResponseInterface
    {
        $this->formData = null;

        return parent::handle($args);
    }

    #[\Override]
    protected function execute(Coupon $coupon, MassActionHandlerArgs $args)
    {
        $form = $this->formFactory->create(BaseCouponType::class, $coupon);
        $form->submit($this->getFormData($args));
    }

    /**
     * @param MassActionHandlerArgs $args
     * @return array
     */
    protected function getFormData(MassActionHandlerArgs $args)
    {
        if (!$this->formData) {
            $requestData = $args->getData();

            if (
                !array_key_exists(BaseCouponType::NAME, $requestData)
                || !is_array($requestData[BaseCouponType::NAME])
            ) {
                throw new LogicException('Required array with form data not found');
            }

            $this->formData = $requestData[BaseCouponType::NAME];
        }

        return $this->formData;
    }

    #[\Override]
    protected function getResponse($entitiesCount)
    {
        $successful = $entitiesCount > 0;
        $options = ['count' => $entitiesCount];

        return new MassActionResponse(
            $successful,
            $this->translator->trans(
                'oro.grid.mass_action.edit.success_message',
                ['%count%' => $entitiesCount]
            ),
            $options
        );
    }
}
