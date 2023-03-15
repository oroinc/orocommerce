<?php

namespace Oro\Bundle\PromotionBundle\Handler;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\PromotionBundle\Entity\AppliedCoupon;
use Oro\Bundle\PromotionBundle\Entity\Coupon;
use Oro\Bundle\PromotionBundle\Exception\LogicException;
use Oro\Bundle\PromotionBundle\Provider\EntityCouponsProviderInterface;
use Oro\Bundle\PromotionBundle\ValidationService\CouponApplicabilityValidationService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Handle coupon applicability and apply it by code.
 */
class FrontendCouponHandler extends AbstractCouponHandler
{
    /**
     * @var CouponApplicabilityValidationService
     */
    private $couponApplicabilityValidationService;

    /**
     * @var EntityCouponsProviderInterface
     */
    private $entityCouponsProvider;

    /**
     * @var ConfigManager
     */
    private $configManager;

    /**
     * @var array
     */
    private $skippedFilters = [];

    public function setCouponApplicabilityValidationService(
        CouponApplicabilityValidationService $couponApplicabilityValidationService
    ) {
        $this->couponApplicabilityValidationService = $couponApplicabilityValidationService;
    }

    public function setEntityCouponsProviderService(EntityCouponsProviderInterface $entityCouponsProvider)
    {
        $this->entityCouponsProvider = $entityCouponsProvider;
    }

    public function setConfigManager(ConfigManager $configManager)
    {
        $this->configManager = $configManager;
    }

    public function disableFilter(string $filterClass)
    {
        $this->skippedFilters[$filterClass] = true;
    }

    /**
     * {@inheritdoc}
     */
    public function handle(Request $request)
    {
        $coupon = $this->getCouponForValidation($request);
        if (!$coupon) {
            return new JsonResponse([
                'success' => false,
                'errors' => ['oro.promotion.coupon.violation.invalid_coupon_code']
            ]);
        }

        $entity = $this->getActualizedEntity($request);
        $errors = $this->couponApplicabilityValidationService->getViolations(
            $coupon,
            $entity,
            $this->skippedFilters
        );

        if (empty($errors) && !$this->promotionAwareHelper->isPromotionAware($entity)) {
            $this->saveAppliedCoupon($coupon, $entity);
        }

        return new JsonResponse(['success' => empty($errors), 'errors' => $errors]);
    }

    /**
     * {@inheritdoc}
     */
    protected function getCouponForValidation(Request $request)
    {
        $couponCode = $request->request->get('couponCode');
        if (!$couponCode) {
            throw new LogicException('Coupon code is not specified in request parameters');
        }

        $caseInsensitive = (bool)$this->configManager->get('oro_promotion.case_insensitive_coupon_search');

        return $this->getRepository(Coupon::class)->getSingleCouponByCode($couponCode, $caseInsensitive);
    }

    private function saveAppliedCoupon(Coupon $coupon, object $entity)
    {
        $appliedCoupon = $this->entityCouponsProvider->createAppliedCouponByCoupon($coupon);
        $entity->addAppliedCoupon($appliedCoupon);

        $manager = $this->registry->getManagerForClass(AppliedCoupon::class);
        $manager->persist($appliedCoupon);
        $manager->flush($appliedCoupon);
    }
}
