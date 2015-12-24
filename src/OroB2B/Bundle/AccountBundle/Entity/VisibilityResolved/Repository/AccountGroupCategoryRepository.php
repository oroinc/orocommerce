<?php

namespace OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * Composite primary key fields order:
 *  - accountGroup
 *  - category
 */
class AccountGroupCategoryRepository extends EntityRepository
{
}
