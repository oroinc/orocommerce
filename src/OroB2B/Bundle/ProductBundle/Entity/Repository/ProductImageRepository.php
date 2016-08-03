<?php

namespace OroB2B\Bundle\ProductBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;

use Oro\Bundle\AttachmentBundle\Entity\File;

use OroB2B\Bundle\ProductBundle\Entity\Product;

class ProductImageRepository extends EntityRepository
{
    /**
     * @param File $image
     *
     * @return null|Product
     */
    public function findOneByImage(File $image)
    {
        return $this->findOneBy(['image' => $image]);
    }
}
