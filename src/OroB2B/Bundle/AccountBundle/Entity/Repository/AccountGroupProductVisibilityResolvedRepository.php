<?php

namespace OroB2B\Bundle\AccountBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;

use OroB2B\Bundle\AccountBundle\Entity\AccountGroup;
use OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\AccountGroupProductVisibilityResolved;
use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\WebsiteBundle\Entity\Website;

/**
 * Composite primary key fields order:
 *  - accountGroup
 *  - website
 *  - product
 */
class AccountGroupProductVisibilityResolvedRepository extends EntityRepository
{
    use ResolvedEntityRepositoryTrait;

    /**
     * @param AccountGroup $accountGroup
     * @param Product $product
     * @param Website $website
     * @return null|AccountGroupProductVisibilityResolved
     */
    public function findByPrimaryKey(AccountGroup $accountGroup, Product $product, Website $website)
    {
        return $this->findOneBy(['accountGroup' => $accountGroup, 'website' => $website, 'product' => $product]);
    }
}
