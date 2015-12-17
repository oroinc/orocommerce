<?php

namespace OroB2B\Bundle\AccountBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Entity\Visibility\AccountProductVisibility;
use OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\AccountProductVisibilityResolved;
use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\WebsiteBundle\Entity\Website;

/**
 * Composite primary key fields order:
 *  - account
 *  - website
 *  - product
 */
class AccountProductVisibilityResolvedRepository extends EntityRepository
{
    use ResolvedEntityRepositoryTrait;

    /**
     * @param Account $account
     * @param Product $product
     * @param Website $website
     * @return null|AccountProductVisibilityResolved
     */
    public function findByPrimaryKey(Account $account, Product $product, Website $website)
    {
        return $this->findOneBy(['account' => $account, 'website' => $website, 'product' => $product]);
    }

    /**
     * Set specified visibility to all resolved entities with fallback to current product
     *
     * @param Website $website
     * @param Product $product
     * @param int $visibility
     */
    public function updateCurrentProductRelatedEntities(Website $website, Product $product, $visibility)
    {
        $affectedEntitiesDql = $this->getEntityManager()->createQueryBuilder()
            ->select('apv.id')
            ->from('OroB2BAccountBundle:Visibility\AccountProductVisibility', 'apv')
            ->andWhere('apv.website = :website')
            ->andWhere('apv.product = :product')
            ->andWhere('apv.visibility = :visibility')
            ->getQuery()
            ->getDQL();

        $this->createQueryBuilder('apvr')
            ->update('OroB2BAccountBundle:VisibilityResolved\AccountProductVisibilityResolved', 'apvr')
            ->set('apvr.visibility', $visibility)
            ->where('IDENTITY(apvr.sourceProductVisibility) IN (' . $affectedEntitiesDql . ')')
            ->setParameter('website', $website)
            ->setParameter('product', $product)
            ->setParameter('visibility', AccountProductVisibility::CURRENT_PRODUCT)
            ->getQuery()
            ->execute();
    }
}
