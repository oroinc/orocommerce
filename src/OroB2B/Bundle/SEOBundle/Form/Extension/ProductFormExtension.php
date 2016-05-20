<?php

namespace OroB2B\Bundle\SEOBundle\Form\Extension;

use Oro\Bundle\EntityBundle\ORM\OroEntityManager;

use OroB2B\Bundle\ProductBundle\Form\Type\ProductType;

use Doctrine\Common\Persistence\ManagerRegistry;

class ProductFormExtension extends BaseMetaFormExtension
{
    /**
     * {@inheritdoc}
     */
    public function getExtendedType()
    {
        return ProductType::NAME;
    }
}
