<?php

namespace Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved;

use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\CustomerGroupProductVisibility;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\Repository\CustomerGroupProductRepository;

/**
* Entity that represents Customer Group Product Visibility Resolved
*
*/
#[ORM\Entity(repositoryClass: CustomerGroupProductRepository::class)]
#[ORM\Table(name: 'oro_cus_grp_prod_vsb_resolv')]
class CustomerGroupProductVisibilityResolved extends BaseProductVisibilityResolved
{
    #[ORM\ManyToOne(targetEntity: CustomerGroupProductVisibility::class)]
    #[ORM\JoinColumn(name: 'source_product_visibility', referencedColumnName: 'id', onDelete: 'CASCADE')]
    protected ?CustomerGroupProductVisibility $sourceProductVisibility = null;

    public function __construct(Scope $scope, Product $product)
    {
        parent::__construct($scope, $product);
    }

    /**
     * @return CustomerGroupProductVisibility
     */
    public function getSourceProductVisibility()
    {
        return $this->sourceProductVisibility;
    }

    /**
     * @param CustomerGroupProductVisibility $sourceProductVisibility
     * @return $this
     */
    public function setSourceProductVisibility(CustomerGroupProductVisibility $sourceProductVisibility)
    {
        $this->sourceProductVisibility = $sourceProductVisibility;

        return $this;
    }
}
