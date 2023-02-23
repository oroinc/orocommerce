<?php

namespace Oro\Bundle\PricingBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\CustomizeLoadedData\CustomizeLoadedDataContext;
use Oro\Bundle\ApiBundle\Request\DataType;
use Oro\Bundle\ApiBundle\Request\ValueTransformer;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Provider\CustomerUserRelationsProvider;
use Oro\Bundle\CustomerBundle\Security\Token\AnonymousCustomerUserToken;
use Oro\Bundle\PricingBundle\Manager\UserCurrencyManager;
use Oro\Bundle\PricingBundle\Model\ProductPriceScopeCriteriaFactoryInterface;
use Oro\Bundle\PricingBundle\Model\ProductPriceScopeCriteriaInterface;
use Oro\Bundle\PricingBundle\Storage\ProductPriceStorageInterface;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\WebsiteBundle\Manager\WebsiteManager;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

/**
 * Computes a value of "prices" field for each element in a collection of Product entities.
 */
class ComputeProductPrices implements ProcessorInterface
{
    private const FIELD_NAME = 'prices';

    private ProductPriceStorageInterface $priceStorage;
    private ProductPriceScopeCriteriaFactoryInterface $priceScopeCriteriaFactory;
    private CustomerUserRelationsProvider $relationsProvider;
    private UserCurrencyManager $currencyManager;
    private TokenStorageInterface $tokenStorage;
    private WebsiteManager $websiteManager;
    private DoctrineHelper $doctrineHelper;
    private ValueTransformer $valueTransformer;

    public function __construct(
        ProductPriceStorageInterface $priceStorage,
        ProductPriceScopeCriteriaFactoryInterface $priceScopeCriteriaFactory,
        CustomerUserRelationsProvider $relationsProvider,
        UserCurrencyManager $currencyManager,
        TokenStorageInterface $tokenStorage,
        WebsiteManager $websiteManager,
        DoctrineHelper $doctrineHelper,
        ValueTransformer $valueTransformer
    ) {
        $this->priceStorage = $priceStorage;
        $this->priceScopeCriteriaFactory = $priceScopeCriteriaFactory;
        $this->relationsProvider = $relationsProvider;
        $this->currencyManager = $currencyManager;
        $this->tokenStorage = $tokenStorage;
        $this->websiteManager = $websiteManager;
        $this->doctrineHelper = $doctrineHelper;
        $this->valueTransformer = $valueTransformer;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var CustomizeLoadedDataContext $context */

        $data = $context->getData();

        if ($context->isFieldRequestedForCollection(self::FIELD_NAME, $data)) {
            $productIdFieldName = $context->getResultFieldName('id');
            $context->setData($this->applyPrices($context, $data, $productIdFieldName));
        }
    }

    private function applyPrices(
        CustomizeLoadedDataContext $context,
        array $data,
        string $productIdFieldName
    ): array {
        $prices = $this->getPrices($context, $context->getIdentifierValues($data, $productIdFieldName));
        foreach ($data as $key => $item) {
            $productId = $item[$productIdFieldName];
            $data[$key][self::FIELD_NAME] = $prices[$productId] ?? [];
        }

        return $data;
    }

    /**
     * @param CustomizeLoadedDataContext $context
     * @param int[]                      $productIds
     *
     * @return array [product id => [price, ...], ...]
     */
    private function getPrices(CustomizeLoadedDataContext $context, array $productIds): array
    {
        $products = [];
        foreach ($productIds as $productId) {
            $products[] = $this->doctrineHelper->getEntityReference(Product::class, $productId);
        }

        $prices = $this->priceStorage->getPrices(
            $this->getScopeCriteria(),
            $products,
            null,
            [$this->currencyManager->getUserCurrency()]
        );

        $result = [];
        $normalizationContext = $context->getNormalizationContext();
        foreach ($prices as $price) {
            $productId = $price->getProduct()->getId();
            $result[$productId][] = [
                'price'      => $this->valueTransformer->transformValue(
                    $price->getPrice()->getValue(),
                    DataType::MONEY,
                    $normalizationContext
                ),
                'currencyId' => $price->getPrice()->getCurrency(),
                'quantity'   => $this->valueTransformer->transformValue(
                    $price->getQuantity(),
                    DataType::DECIMAL,
                    $normalizationContext
                ),
                'unit'       => $price->getUnit()->getCode()
            ];
        }

        return $result;
    }

    private function getScopeCriteria(): ProductPriceScopeCriteriaInterface
    {
        return $this->priceScopeCriteriaFactory->create(
            $this->websiteManager->getCurrentWebsite(),
            $this->relationsProvider->getCustomerIncludingEmpty($this->getCustomerUser())
        );
    }

    private function getCustomerUser(): ?CustomerUser
    {
        $customerUser = null;
        $token = $this->tokenStorage->getToken();
        if ($token instanceof AnonymousCustomerUserToken) {
            $visitor = $token->getVisitor();
            if ($visitor) {
                $customerUser = $visitor->getCustomerUser();
            }
        } elseif ($token instanceof TokenInterface) {
            $user = $token->getUser();
            if ($user instanceof CustomerUser) {
                $customerUser = $user;
            }
        }

        return $customerUser;
    }
}
