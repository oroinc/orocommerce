<?php

namespace OroB2B\Bundle\PricingBundle\Model;

use Doctrine\ORM\QueryBuilder;

use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

use OroB2B\Bundle\AccountBundle\Entity\AccountUser;

class FrontendProductListModifier
{
    /**
     * @var TokenStorageInterface
     */
    protected $tokenStorage;

    /**
     * @var PriceListTreeHandler
     */
    protected $priceListTreeHandler;

    /**
     * @param TokenStorageInterface $tokenStorage
     * @param PriceListTreeHandler $priceListTreeHandler
     */
    public function __construct(TokenStorageInterface $tokenStorage, PriceListTreeHandler $priceListTreeHandler)
    {
        $this->tokenStorage = $tokenStorage;
        $this->priceListTreeHandler = $priceListTreeHandler;
    }

    /**
     * @param QueryBuilder $queryBuilder
     * @param string|null $currency
     */
    public function applyPriceListLimitations(QueryBuilder $queryBuilder, $currency = null)
    {
        $token = $this->tokenStorage->getToken();
        /** @var AccountUser $user */
        if ($token && ($user = $token->getUser()) instanceof AccountUser) {
            $priceList = $this->priceListTreeHandler->getPriceList($user);

            if ($priceList) {
                $rootAliases = $queryBuilder->getRootAliases();
                $rootAlias = $rootAliases[0];

                // Select only products that are in specific price list
                $limitationQb = $queryBuilder->getEntityManager()->createQueryBuilder();
                $limitationQb->from('OroB2BPricingBundle:ProductPrice', '_productPrice')
                    ->select('IDENTITY(_productPrice.product)')
                    ->where($limitationQb->expr()->eq('_productPrice.priceList', ':_priceList'))
                    ->andWhere($limitationQb->expr()->eq('_productPrice.product', $rootAlias));

                if ($currency) {
                    $limitationQb
                        ->andWhere($queryBuilder->expr()->eq('_productPrice.currency', ':currency'));
                    $queryBuilder
                        ->setParameter('currency', strtoupper($currency));
                }

                $queryBuilder
                    ->andWhere($queryBuilder->expr()->exists($limitationQb))
                    ->setParameter('_priceList', $priceList);
            }
        }
    }
}
