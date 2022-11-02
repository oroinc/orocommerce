<?php

namespace Oro\Bundle\PromotionBundle\Twig;

use Doctrine\Common\Collections\Collection;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\PromotionBundle\CouponGeneration\Code\CodeGeneratorInterface;
use Oro\Bundle\PromotionBundle\CouponGeneration\Options\CodeGenerationOptions;
use Oro\Bundle\PromotionBundle\Entity\AppliedPromotion;
use Oro\Bundle\PromotionBundle\Entity\Repository\AppliedPromotionRepository;
use Oro\Bundle\PromotionBundle\Layout\DataProvider\DiscountsInformationDataProvider;
use Psr\Container\ContainerInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Provides a Twig function to retrieve line item discounts information:
 *   - line_items_discounts
 *
 * Provides Twig functions to retrieve information about applied promotions:
 *   - oro_promotion_prepare_applied_promotions_info
 *   - oro_promotion_get_applied_promotions_info
 *
 * Provides a Twig function to generate a coupon code:
 *   - oro_promotion_generate_coupon_code
 */
class PromotionExtension extends AbstractExtension implements ServiceSubscriberInterface
{
    private ContainerInterface $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new TwigFunction('line_items_discounts', [$this, 'getLineItemsDiscounts']),
            new TwigFunction('oro_promotion_prepare_applied_promotions_info', [$this, 'prepareAppliedPromotionsInfo']),
            new TwigFunction('oro_promotion_get_applied_promotions_info', [$this, 'getAppliedPromotionsInfo']),
            new TwigFunction('oro_promotion_generate_coupon_code', [$this, 'generateCouponCode']),
        ];
    }

    /**
     * @param object $sourceEntity
     *
     * @return array
     */
    public function getLineItemsDiscounts($sourceEntity)
    {
        $lineItemsDiscounts = $this->getDiscountsInformationDataProvider()
            ->getDiscountLineItemDiscounts($sourceEntity);

        $discounts = [];
        foreach ($sourceEntity->getLineItems() as $lineItem) {
            $discounts[$lineItem->getId()] = null;
            if ($lineItemsDiscounts->contains($lineItem)) {
                $discount = $lineItemsDiscounts->get($lineItem);
                /** @var Price $discountPrice */
                $discountPrice = $discount['total'];
                $discounts[$lineItem->getId()] = [
                    'value' => $discountPrice->getValue(),
                    'currency' => $discountPrice->getCurrency(),
                ];
            }
        }

        return $discounts;
    }

    /**
     * @param Collection|AppliedPromotion[] $appliedPromotions
     * @return array
     */
    public function prepareAppliedPromotionsInfo($appliedPromotions): array
    {
        $items = [];
        foreach ($appliedPromotions as $appliedPromotion) {
            $couponCode = null;
            $sourceCouponId = null;
            if ($appliedPromotion->getAppliedCoupon()) {
                $couponCode = $appliedPromotion->getAppliedCoupon()->getCouponCode();
                $sourceCouponId = $appliedPromotion->getAppliedCoupon()->getSourceCouponId();
            }

            $item = [
                'id' => $appliedPromotion->getId(),
                'couponCode' => $couponCode,
                'promotionName' => $appliedPromotion->getPromotionName(),
                'active' => $appliedPromotion->isActive(),
                'amount' => 0,
                'type' => $appliedPromotion->getType(),
                'sourcePromotionId' => $appliedPromotion->getSourcePromotionId(),
                'sourceCouponId' => $sourceCouponId
            ];

            foreach ($appliedPromotion->getAppliedDiscounts() as $appliedDiscount) {
                $item['amount'] += $appliedDiscount->getAmount();
                $item['currency'] = $appliedDiscount->getCurrency();
            }

            $items[] = $item;
        }

        usort($items, function (array $a, array $b) {
            if (empty($a['id']) && empty($b['id'])) {
                return 0;
            }

            if (empty($a['id'])) {
                return 1;
            }

            if (empty($b['id'])) {
                return -1;
            }

            return (int)$a['id'] - (int)$b['id'];
        });

        return $items;
    }

    public function getAppliedPromotionsInfo(Order $order): array
    {
        return $this->getAppliedPromotionRepository()->getAppliedPromotionsInfo($order);
    }

    /**
     * @param CodeGenerationOptions $codeGenerationOptions
     * @return string
     */
    public function generateCouponCode(CodeGenerationOptions $codeGenerationOptions)
    {
        return $this->getCouponCodeGenerator()->generateOne($codeGenerationOptions);
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedServices()
    {
        return [
            'oro_promotion.layout.discount_information_data_provider' => DiscountsInformationDataProvider::class,
            'oro_promotion.coupon_generation.code_generator' => CodeGeneratorInterface::class,
            ManagerRegistry::class
        ];
    }

    private function getDiscountsInformationDataProvider(): DiscountsInformationDataProvider
    {
        return $this->container->get('oro_promotion.layout.discount_information_data_provider');
    }

    private function getAppliedPromotionRepository(): AppliedPromotionRepository
    {
        return $this->container->get(ManagerRegistry::class)
            ->getManagerForClass(AppliedPromotion::class)
            ->getRepository(AppliedPromotion::class);
    }

    private function getCouponCodeGenerator(): CodeGeneratorInterface
    {
        return $this->container->get('oro_promotion.coupon_generation.code_generator');
    }
}
