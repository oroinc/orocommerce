<?php

namespace Oro\Bundle\PromotionBundle\EventListener;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Event\CheckoutRequestEvent;
use Oro\Bundle\PromotionBundle\Entity\AppliedCoupon;
use Oro\Bundle\PromotionBundle\Entity\Coupon;
use Oro\Bundle\PromotionBundle\Handler\FrontendCouponRemoveHandler;
use Oro\Bundle\PromotionBundle\ValidationService\CouponValidationService;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Remove the coupon from the checkout if it is not valid.
 */
class FrontendCouponRemoveListener
{
    public function __construct(
        private ManagerRegistry $managerRegistry,
        private TranslatorInterface $translator,
        private FrontendCouponRemoveHandler $frontendCouponRemoveHandler,
        private CouponValidationService $validationService,
        private iterable $couponValidators
    ) {
    }

    public function onCheckoutRequest(CheckoutRequestEvent $event): void
    {
        $flashBag = $event->getRequest()->getSession()->getFlashBag();
        $checkout = $event->getCheckout();
        $entityManager = $this->managerRegistry->getManager();
        /** @var AppliedCoupon $appliedCoupon */
        foreach ($checkout->getAppliedCoupons() as $appliedCoupon) {
            if (!$appliedCoupon->getSourceCouponId()) {
                continue;
            }

            /** @var Coupon $coupon */
            $coupon = $entityManager->find(Coupon::class, $appliedCoupon->getSourceCouponId());
            if (!$coupon) {
                continue;
            }

            $errors = $this->validateCoupon($coupon, $checkout);
            if (empty($errors)) {
                continue;
            }

            $this->addWaringMessages($flashBag, $coupon, $errors);
            $this->frontendCouponRemoveHandler->handleRemove($checkout, $appliedCoupon);
        }
    }

    private function validateCoupon(Coupon $coupon, Checkout $entity): array
    {
        $violations = [];
        $violations[] = $this->validationService->getViolations($coupon, $entity->getCustomerUser());
        foreach ($this->couponValidators as $validator) {
            $violation = $validator->getViolation($coupon, $entity);
            if ($violation) {
                $violations[] = [$violation];
            }
        }

        return array_filter(array_merge(...$violations));
    }

    private function addWaringMessages(FlashBagInterface $flashBag, Coupon $coupon, array $errors): void
    {
        $couponCode = $coupon->getCode();
        foreach ($errors as $error) {
            $message = $this->translator->trans($error, ['%coupon_name%' => $couponCode]);
            if (!$flashBag->has($message)) {
                $flashBag->add('warning', $message);
            }
        }
    }
}
