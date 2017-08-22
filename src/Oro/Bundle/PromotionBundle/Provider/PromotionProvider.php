<?php

namespace Oro\Bundle\PromotionBundle\Provider;

use Doctrine\Common\Persistence\ManagerRegistry;
use Oro\Bundle\PromotionBundle\Context\ContextDataConverterInterface;
use Oro\Bundle\PromotionBundle\Entity\AppliedDiscountsAwareInterface;
use Oro\Bundle\PromotionBundle\Entity\DiscountConfiguration;
use Oro\Bundle\PromotionBundle\Entity\Promotion;
use Oro\Bundle\PromotionBundle\Entity\PromotionDataInterface;
use Oro\Bundle\PromotionBundle\Normalizer\NormalizerInterface;
use Oro\Bundle\RuleBundle\RuleFiltration\RuleFiltrationServiceInterface;

class PromotionProvider
{
    /**
     * @var ManagerRegistry
     */
    private $registry;

    /**
     * @var RuleFiltrationServiceInterface
     */
    private $ruleFiltrationService;

    /**
     * @var ContextDataConverterInterface
     */
    private $contextDataConverter;

    /**
     * @var NormalizerInterface
     */
    private $promotionNormalizer;

    /**
     * @param ManagerRegistry $registry
     * @param RuleFiltrationServiceInterface $ruleFiltrationService
     * @param ContextDataConverterInterface $contextDataConverter
     * @param NormalizerInterface $promotionNormalizer
     */
    public function __construct(
        ManagerRegistry $registry,
        RuleFiltrationServiceInterface $ruleFiltrationService,
        ContextDataConverterInterface $contextDataConverter,
        NormalizerInterface $promotionNormalizer
    ) {
        $this->registry = $registry;
        $this->ruleFiltrationService = $ruleFiltrationService;
        $this->contextDataConverter = $contextDataConverter;
        $this->promotionNormalizer = $promotionNormalizer;
    }

    /**
     * @param object $sourceEntity
     * @return array|PromotionDataInterface[]
     */
    public function getPromotions($sourceEntity): array
    {
        $promotions = [];

        if ($sourceEntity instanceof AppliedDiscountsAwareInterface) {
            $promotions = $this->getAppliedPromotions($sourceEntity);
        }

        $promotions = array_merge($promotions, $this->getAllPromotions());

        return $this->filterPromotions($sourceEntity, $promotions);
    }

    /**
     * Checks whether promotion has been already applied to a given source entity.
     *
     * @param object $sourceEntity
     * @param PromotionDataInterface $promotion
     * @return bool
     */
    public function isPromotionApplied($sourceEntity, PromotionDataInterface $promotion): bool
    {
        $promotions = $this->getPromotions($sourceEntity);

        foreach ($promotions as $appliedPromotion) {
            if ($appliedPromotion->getId() === $promotion->getId()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Checks whether promotion can be applied to a given source entity.
     *
     * @param $sourceEntity
     * @param PromotionDataInterface $promotion
     * @return bool
     */
    public function isPromotionApplicable($sourceEntity, PromotionDataInterface $promotion): bool
    {
        return !empty($this->filterPromotions($sourceEntity, [$promotion]));
    }

    /**
     * @param object $sourceEntity
     * @param array|PromotionDataInterface[] $promotions
     * @return array|\Oro\Bundle\RuleBundle\Entity\RuleOwnerInterface[]
     */
    private function filterPromotions($sourceEntity, array $promotions): array
    {
        $contextData = $this->contextDataConverter->getContextData($sourceEntity);

        return $this->ruleFiltrationService->getFilteredRuleOwners($promotions, $contextData);
    }

    /**
     * @return array|PromotionDataInterface[]
     */
    private function getAllPromotions()
    {
        return $this->registry
            ->getManagerForClass(Promotion::class)
            ->getRepository(Promotion::class)
            ->findAll();
    }

    /**
     * @param AppliedDiscountsAwareInterface $sourceEntity
     * @return array|PromotionDataInterface[]
     */
    private function getAppliedPromotions(AppliedDiscountsAwareInterface $sourceEntity)
    {
        $appliedPromotions = [];
        foreach ($sourceEntity->getAppliedDiscounts() as $appliedDiscount) {
            // Early check for duplicated promotions for exiting promotions
            $discountPromotion = $appliedDiscount->getPromotion();
            if ($discountPromotion && array_key_exists($discountPromotion->getId(), $appliedPromotions)) {
                continue;
            }

            $appliedPromotion = $this->promotionNormalizer->denormalize($appliedDiscount->getPromotionData());
            // Check duplicated promotions for removed promotions
            if (array_key_exists($appliedPromotion->getId(), $appliedPromotions)) {
                continue;
            }

            $discountConfiguration = new DiscountConfiguration();
            $discountConfiguration->setType($appliedDiscount->getType());
            $discountConfiguration->setOptions($appliedDiscount->getConfigOptions());
            $appliedPromotion->setDiscountConfiguration($discountConfiguration);

            $appliedPromotions[$appliedPromotion->getId()] = $appliedPromotion;
        }

        return array_values($appliedPromotions);
    }
}
