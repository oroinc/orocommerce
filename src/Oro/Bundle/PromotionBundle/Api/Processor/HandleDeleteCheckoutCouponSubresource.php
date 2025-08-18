<?php

namespace Oro\Bundle\PromotionBundle\Api\Processor;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Processor\Subresource\ChangeSubresourceContext;
use Oro\Bundle\ApiBundle\Processor\Subresource\Shared\SaveParentEntity;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\PromotionBundle\Api\Model\ChangeCouponRequest;
use Oro\Bundle\PromotionBundle\Manager\FrontendAppliedCouponManager;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Remove applied coupons from Checkout entity.
 */
class HandleDeleteCheckoutCouponSubresource implements ProcessorInterface
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
        $isCouponRemoved = $this->frontendAppliedCouponManager->removeAppliedCouponByCode(
            $checkout,
            $changeCouponRequest->getCouponCode(),
            $errors
        );
        if ($isCouponRemoved) {
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
