<?php

namespace Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved;

use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\CustomerProductVisibility;

/**
 * @ORM\Entity(
 *    repositoryClass="Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\Repository\CustomerProductRepository"
 * )
 * @ORM\Table(name="oro_cus_prod_vsb_resolv")
 */
class CustomerProductVisibilityResolved extends BaseProductVisibilityResolved
{
    const VISIBILITY_FALLBACK_TO_ALL = 2;

    /**
     * @var CustomerProductVisibility
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\VisibilityBundle\Entity\Visibility\CustomerProductVisibility")
     * @ORM\JoinColumn(name="source_product_visibility", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $sourceProductVisibility;

    public function __construct(Scope $scope, Product $product)
    {
        parent::__construct($scope, $product);
    }

    /**
     * @return CustomerProductVisibility
     */
    public function getSourceProductVisibility()
    {
        return $this->sourceProductVisibility;
    }

    /**
     * @param CustomerProductVisibility $sourceProductVisibility
     * @return $this
     */
    public function setSourceProductVisibility(CustomerProductVisibility $sourceProductVisibility)
    {
        $this->sourceProductVisibility = $sourceProductVisibility;

        return $this;
    }
}
