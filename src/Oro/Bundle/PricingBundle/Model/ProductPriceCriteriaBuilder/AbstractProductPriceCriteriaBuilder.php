<?php

declare(strict_types=1);

namespace Oro\Bundle\PricingBundle\Model\ProductPriceCriteriaBuilder;

use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\PricingBundle\Manager\UserCurrencyManager;
use Oro\Bundle\PricingBundle\Model\ProductPriceCriteria;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;
use Symfony\Contracts\Service\ResetInterface;

/**
 * Implements base functionality needed by product price criteria builders.
 */
abstract class AbstractProductPriceCriteriaBuilder implements
    ProductPriceCriteriaBuilderInterface,
    ResetInterface,
    LoggerAwareInterface
{
    use LoggerAwareTrait;

    protected ManagerRegistry $managerRegistry;

    protected UserCurrencyManager $userCurrencyManager;

    protected ?Product $product = null;

    protected ?ProductUnit $productUnit = null;

    protected ?float $quantity = 0.0;

    protected ?string $currency = null;

    public function __construct(ManagerRegistry $managerRegistry, UserCurrencyManager $userCurrencyManager)
    {
        $this->managerRegistry = $managerRegistry;
        $this->userCurrencyManager = $userCurrencyManager;

        $this->logger = new NullLogger();
    }

    abstract protected function doCreate(): ?ProductPriceCriteria;

    #[\Override]
    public function create(): ?ProductPriceCriteria
    {
        try {
            return $this->doCreate();
        } catch (\Throwable $throwable) {
            $this->logger->error(
                'Failed to create a product price criteria for: product #{product_id},'
                . 'unit "{unit_code}", quantity "{quantity}", currency "{currency}". Error: {message}',
                [
                    'throwable' => $throwable,
                    'message' => $throwable->getMessage(),
                    'product_id' => $this->product?->getId(),
                    'unit_code' => $this->productUnit?->getCode(),
                    'quantity' => $this->quantity,
                    'currency' => $this->getCurrencyWithFallback(),
                ]
            );

            return null;
        } finally {
            $this->reset();
        }
    }

    #[\Override]
    public function reset(): void
    {
        $this->product = $this->productUnit = $this->quantity = $this->currency = null;
    }

    #[\Override]
    public function setProduct(?Product $product): self
    {
        $this->product = $product;

        return $this;
    }

    #[\Override]
    public function setProductUnit(?ProductUnit $productUnit): self
    {
        $this->productUnit = $productUnit;

        return $this;
    }

    #[\Override]
    public function setProductUnitCode(string $productUnitCode): self
    {
        /** @var EntityManager $entityManager */
        $entityManager = $this->managerRegistry->getManagerForClass(ProductUnit::class);
        $this->productUnit = $entityManager->getReference(ProductUnit::class, $productUnitCode);

        return $this;
    }

    #[\Override]
    public function setQuantity(?float $quantity): self
    {
        $this->quantity = $quantity;

        return $this;
    }

    #[\Override]
    public function setCurrency(?string $currency): self
    {
        $this->currency = $currency;

        return $this;
    }

    protected function getCurrencyWithFallback(): ?string
    {
        return $this->currency ??
            $this->userCurrencyManager->getUserCurrency() ?: $this->userCurrencyManager->getDefaultCurrency();
    }
}
