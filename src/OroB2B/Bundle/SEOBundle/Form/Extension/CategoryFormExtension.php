<?php

namespace OroB2B\Bundle\SEOBundle\Form\Extension;

use Oro\Bundle\EntityBundle\ORM\OroEntityManager;

use OroB2B\Bundle\CatalogBundle\Form\Type\CategoryType;

use Doctrine\Common\Persistence\ManagerRegistry;

class CategoryFormExtension extends BaseMetaFormExtension
{
    /**
     * {@inheritdoc}
     */
    public function getExtendedType()
    {
        return CategoryType::NAME;
    }
}
