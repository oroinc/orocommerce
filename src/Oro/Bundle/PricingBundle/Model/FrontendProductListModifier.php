<?php

namespace Oro\Bundle\PricingBundle\Model;

use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureCheckerAwareInterface;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureCheckerHolderTrait;
use Oro\Bundle\PricingBundle\Entity\BasePriceList;
use Oro\Bundle\PricingBundle\Entity\CombinedProductPrice;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Adds query builder limitation subquery based on currency and price list
 */
class FrontendProductListModifier implements FeatureCheckerAwareInterface
{
    use FeatureCheckerHolderTrait;

    /**
     * @var TokenStorageInterface
     */
    protected $tokenStorage;

    /**
     * @var CombinedPriceListTreeHandler
     */
    protected $priceListTreeHandler;

    public function __construct(TokenStorageInterface $tokenStorage, CombinedPriceListTreeHandler $priceListTreeHandler)
    {
        $this->tokenStorage = $tokenStorage;
        $this->priceListTreeHandler = $priceListTreeHandler;
    }

    /**
     * @param QueryBuilder $queryBuilder
     * @param string|null $currency
     * @param null|BasePriceList $priceList
     */
    public function applyPriceListLimitations(
        QueryBuilder $queryBuilder,
        $currency = null,
        BasePriceList $priceList = null
    ) {
        if (!$this->isFeaturesEnabled()) {
            return;
        }

        $token = $this->tokenStorage->getToken();
        /** @var CustomerUser $user */
        if ($token && ($user = $token->getUser()) instanceof CustomerUser) {
            $priceList = $priceList ?: $this->priceListTreeHandler->getPriceList($user->getCustomer());

            if ($priceList) {
                list($rootAlias) = $queryBuilder->getRootAliases();

                $parametersCount = $queryBuilder->getParameters()->count();

                $productPriceAlias = 'productPrice_' . $parametersCount;
                $priceListParameterName = 'priceList_' . $parametersCount;

                // Select only products that are in specific price list
                $limitationQb = $queryBuilder->getEntityManager()->createQueryBuilder();
                $limitationQb->from(CombinedProductPrice::class, $productPriceAlias)
                    ->select('IDENTITY(' . $this->getParameterName($productPriceAlias, 'product') . ')')
                    ->where($limitationQb->expr()->eq(
                        $this->getParameterName($productPriceAlias, 'priceList'),
                        ':' . $priceListParameterName
                    ))
                    ->andWhere($limitationQb->expr()->eq(
                        $this->getParameterName($productPriceAlias, 'product'),
                        $rootAlias
                    ));

                if ($currency) {
                    $currencyParameterName = 'currency_' . $parametersCount;

                    $limitationQb->andWhere($queryBuilder->expr()->eq(
                        $this->getParameterName($productPriceAlias, 'currency'),
                        ':' . $currencyParameterName
                    ));
                    $queryBuilder->setParameter($currencyParameterName, strtoupper($currency));
                }

                $queryBuilder->andWhere($queryBuilder->expr()->exists($limitationQb))
                    ->setParameter($priceListParameterName, $priceList);
            }
        }
    }

    /**
     * @param string $alias
     * @param string $parameter
     * @return string
     */
    protected function getParameterName($alias, $parameter)
    {
        return $alias . '.' . $parameter;
    }
}
