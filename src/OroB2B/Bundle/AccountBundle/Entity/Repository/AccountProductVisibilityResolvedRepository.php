<?php

namespace OroB2B\Bundle\AccountBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Entity\Visibility\AccountProductVisibility;
use OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\AccountProductVisibilityResolved;
use OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\Repository\BasicOperationRepositoryTrait;
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
    use BasicOperationRepositoryTrait;

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
}
