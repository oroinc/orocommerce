<?php

namespace Oro\Bundle\CatalogBundle\EventListener\ORM;

use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Entity\CategoryTitle;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\OrganizationBundle\Entity\Organization;

/**
 * Create default root category for each new organization created
 */
class OrganizationPersistListener
{
    const ROOT_CATEGORY_NAME = 'All Products';

    /** @var DoctrineHelper */
    private $doctrineHelper;

    public function __construct(DoctrineHelper $doctrineHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
    }

    public function prePersist(Organization $organization)
    {
        $title = new CategoryTitle();
        $title->setString(self::ROOT_CATEGORY_NAME);

        $category = new Category();
        $category->addTitle($title);
        $category->setOrganization($organization);

        $manager = $this->doctrineHelper->getEntityManager(Category::class);

        $manager->persist($category);
    }
}
