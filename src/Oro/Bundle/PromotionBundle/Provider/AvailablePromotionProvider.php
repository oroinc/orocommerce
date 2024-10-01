<?php

namespace Oro\Bundle\PromotionBundle\Provider;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureCheckerHolderTrait;
use Oro\Bundle\PromotionBundle\Context\ContextDataConverterInterface;
use Oro\Bundle\PromotionBundle\Entity\Promotion;
use Oro\Bundle\PromotionBundle\Entity\Repository\PromotionRepository;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;

/**
 * Provides information about promotions available for a specific context.
 */
class AvailablePromotionProvider implements AvailablePromotionProviderInterface
{
    use FeatureCheckerHolderTrait;

    public function __construct(
        private ManagerRegistry $doctrine,
        private TokenAccessorInterface $tokenAccessor
    ) {
    }

    #[\Override]
    public function getAvailablePromotions(array $contextData): array
    {
        if (!$this->isFeaturesEnabled()) {
            return [];
        }

        $organization = $this->tokenAccessor->getOrganization();
        if (null === $organization) {
            return [];
        }

        return $this->getPromotionRepository()->getAvailablePromotions(
            $contextData[ContextDataConverterInterface::CRITERIA],
            $contextData[ContextDataConverterInterface::CURRENCY] ?? null,
            $organization->getId()
        );
    }

    private function getPromotionRepository(): PromotionRepository
    {
        return $this->doctrine->getRepository(Promotion::class);
    }
}
