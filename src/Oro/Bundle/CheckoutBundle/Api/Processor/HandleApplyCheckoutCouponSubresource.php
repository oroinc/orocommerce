<?php

namespace Oro\Bundle\CheckoutBundle\Api\Processor;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Processor\Subresource\ChangeSubresourceContext;
use Oro\Bundle\ApiBundle\Processor\Subresource\Shared\SaveParentEntity;
use Oro\Bundle\CheckoutBundle\Api\Model\ChangeCouponRequest;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\PromotionBundle\Manager\FrontendAppliedCouponManager;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Applies coupons to Checkout entity.
 */
class HandleApplyCheckoutCouponSubresource implements ProcessorInterface
{
    public function __construct(
        private readonly FrontendAppliedCouponManager $frontendAppliedCouponManager,
        private readonly TranslatorInterface $translator
    ) {
    }

    #[\Override]
    public function process(ContextInterface $context): void
    {
        /** @var ChangeSubresourceContext $context */

        /** @var Checkout $checkout */
        $checkout = $context->getParentEntity();
        $associationName = $context->getAssociationName();
        /** @var ChangeCouponRequest $changeCouponRequest */
        $changeCouponRequest = $context->getResult()[$associationName];

        $errors = new ArrayCollection();
        $isCouponApplied = $this->frontendAppliedCouponManager->applyCoupon(
            $checkout,
            $changeCouponRequest->getCouponCode(),
            $errors
        );
        if ($isCouponApplied) {
            $context->setResult([$associationName => $checkout]);
            $context->setProcessed(SaveParentEntity::class);
        } else {
            foreach ($errors as $error) {
                $context->addError(Error::createValidationError(
                    'coupon constraint',
                    $this->translator->trans($error, [], 'jsmessages')
                ));
            }
        }
    }
}
