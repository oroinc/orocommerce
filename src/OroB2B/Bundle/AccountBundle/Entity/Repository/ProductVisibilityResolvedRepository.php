<?php

namespace OroB2B\Bundle\AccountBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;

use OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\ProductVisibilityResolved;
use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\WebsiteBundle\Entity\Website;

/**
 * Composite primary key fields order:
 *  - website
 *  - product
 */
class ProductVisibilityResolvedRepository extends EntityRepository
{
    /**
     * @param Product $product
     * @param Website $website
     * @return null|ProductVisibilityResolved
     */
    public function findByPrimaryKey(Product $product, Website $website)
    {
        return $this->findOneBy(['website' => $website, 'product' => $product]);
    }
}
