<?php

declare(strict_types=1);

namespace Oro\Bundle\PricingBundle\Provider;

use Oro\Bundle\PricingBundle\Entity\ProductPrice;
use Oro\Bundle\PricingBundle\Model\ProductPriceScopeCriteriaInterface;
use Oro\Bundle\SecurityBundle\Acl\BasicPermission;
use Oro\Bundle\UserBundle\Entity\AbstractUser;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Checks ProductPrice VIEW permission for back-office/storefront users before returning product prices.
 *
 * Internal pricing calculations can use the undecorated provider explicitly.
 */
class AclAwareProductPriceProvider implements ProductPriceProviderInterface, MatchedProductPriceProviderInterface
{
    public function __construct(
        private readonly ProductPriceProviderInterface&MatchedProductPriceProviderInterface $inner,
        private readonly AuthorizationCheckerInterface $authorizationChecker,
        private readonly TokenStorageInterface $tokenStorage
    ) {
    }

    #[\Override]
    public function getSupportedCurrencies(ProductPriceScopeCriteriaInterface $scopeCriteria): array
    {
        if (!$this->isGranted()) {
            return [];
        }

        return $this->inner->getSupportedCurrencies($scopeCriteria);
    }

    #[\Override]
    public function getPricesByScopeCriteriaAndProducts(
        ProductPriceScopeCriteriaInterface $scopeCriteria,
        array $products,
        array $currencies,
        ?string $unitCode = null
    ): array {
        if (!$this->isGranted()) {
            return [];
        }

        return $this->inner->getPricesByScopeCriteriaAndProducts(
            $scopeCriteria,
            $products,
            $currencies,
            $unitCode
        );
    }

    #[\Override]
    public function getMatchedPrices(
        array $productPriceCriteria,
        ProductPriceScopeCriteriaInterface $scopeCriteria
    ): array {
        if (!$this->isGranted()) {
            return [];
        }

        return $this->inner->getMatchedPrices(
            $productPriceCriteria,
            $scopeCriteria
        );
    }

    #[\Override]
    public function getMatchedProductPrices(
        array $productsPriceCriteria,
        ProductPriceScopeCriteriaInterface $productPriceScopeCriteria
    ): array {
        if (!$this->isGranted()) {
            return [];
        }

        return $this->inner->getMatchedProductPrices(
            $productsPriceCriteria,
            $productPriceScopeCriteria
        );
    }

    private function isGranted(): bool
    {
        $user = $this->tokenStorage->getToken()?->getUser();

        if (!$user instanceof AbstractUser) {
            return true;
        }

        return $this->authorizationChecker->isGranted(
            BasicPermission::VIEW,
            sprintf('entity:%s', ProductPrice::class)
        );
    }
}
