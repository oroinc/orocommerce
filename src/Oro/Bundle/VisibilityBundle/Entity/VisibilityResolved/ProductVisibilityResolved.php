<?php

namespace Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved;

use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\ProductVisibility;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\Repository\ProductRepository;

/**
* Entity that represents Product Visibility Resolved
*
*/
#[ORM\Entity(repositoryClass: ProductRepository::class)]
#[ORM\Table(name: 'oro_prod_vsb_resolv')]
class ProductVisibilityResolved extends BaseProductVisibilityResolved
{
    #[ORM\ManyToOne(targetEntity: ProductVisibility::class)]
    #[ORM\JoinColumn(
        name: 'source_product_visibility',
        referencedColumnName: 'id',
        nullable: true,
        onDelete: 'CASCADE'
    )]
    protected ?ProductVisibility $sourceProductVisibility = null;

    /**
     * @return ProductVisibility
     */
    public function getSourceProductVisibility()
    {
        return $this->sourceProductVisibility;
    }

    /**
     * @param ProductVisibility|null $sourceProductVisibility
     * @return $this
     */
    public function setSourceProductVisibility(ProductVisibility $sourceProductVisibility = null)
    {
        $this->sourceProductVisibility = $sourceProductVisibility;

        return $this;
    }
}
