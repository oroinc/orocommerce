<?php

namespace Oro\Bundle\CatalogBundle\EventListener\ORM;

use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\OrganizationBundle\Entity\Organization;

/**
 * Create default root category for each new organization created
 */
class OrganizationPersistListener
{
    const ROOT_CATEGORY_NAME = 'All Products';

    /** @var DoctrineHelper */
    private $doctrineHelper;

    /**
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(DoctrineHelper $doctrineHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * @param Organization $organization
     */
    public function prePersist(Organization $organization)
    {
        $title = new LocalizedFallbackValue();
        $title->setString(self::ROOT_CATEGORY_NAME);

        $category = new Category();
        $category->addTitle($title);
        $category->setOrganization($organization);

        $manager = $this->doctrineHelper->getEntityManager(Category::class);

        $manager->persist($category);
    }
}
