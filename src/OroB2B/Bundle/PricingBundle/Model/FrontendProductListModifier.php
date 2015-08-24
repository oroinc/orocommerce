<?php

namespace OroB2B\Bundle\PricingBundle\Model;

use Doctrine\ORM\QueryBuilder;

use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

use OroB2B\Bundle\AccountBundle\Entity\AccountUser;
use OroB2B\Bundle\PricingBundle\Provider\PriceListProvider;

class FrontendProductListModifier
{
    /**
     * @var TokenStorageInterface
     */
    protected $tokenStorage;

    /**
     * @var PriceListProvider
     */
    protected $priceListProvider;

    public function __construct(TokenStorageInterface $tokenStorage, PriceListProvider $priceListProvider)
    {
        $this->tokenStorage = $tokenStorage;
        $this->priceListProvider = $priceListProvider;
    }

    /**
     * @param QueryBuilder $queryBuilder
     */
    public function applyPriceListLimitations(QueryBuilder $queryBuilder)
    {
        $token = $this->tokenStorage->getToken();
        /** @var AccountUser $user */
        if ($token && ($user = $token->getUser()) instanceof AccountUser) {
            $priceList = $this->priceListProvider->getPriceListByAccount($user->getAccount());

            if ($priceList) {
                $rootAliases = $queryBuilder->getRootAliases();
                $rootAlias = $rootAliases[0];

                // Select only products that are in specific price list
                $limitationQb = $queryBuilder->getEntityManager()->createQueryBuilder();
                $limitationQb->from('OroB2BPricingBundle:ProductPrice', '_productPrice')
                    ->select('IDENTITY(_productPrice.product)')
                    ->where($limitationQb->expr()->eq('_productPrice.priceList', ':_priceList'))
                    ->andWhere($limitationQb->expr()->eq('_productPrice.product', $rootAlias));

                $queryBuilder
                    ->andWhere($queryBuilder->expr()->exists($limitationQb))
                    ->setParameter('_priceList', $priceList);
            }
        }
    }
}
