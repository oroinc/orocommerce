<?php

namespace OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved;

use Doctrine\ORM\Mapping as ORM;

use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use OroB2B\Bundle\AccountBundle\Entity\Visibility\ProductVisibility;

/**
 * @ORM\Entity
 * @ORM\Table(name="orob2b_prod_vsb_resolv")
 * @Config
 */
class ProductVisibilityResolved extends BaseProductVisibilityResolved
{
    /**
     * @var ProductVisibility
     *
     * @ORM\ManyToOne(targetEntity="OroB2B\Bundle\AccountBundle\Entity\Visibility\ProductVisibility")
     * @ORM\JoinColumn(name="source_product_visibility", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $sourceProductVisibility;

    /**
     * @return ProductVisibility
     */
    public function getSourceProductVisibility()
    {
        return $this->sourceProductVisibility;
    }

    /**
     * @param ProductVisibility $sourceProductVisibility
     * @return $this
     */
    public function setSourceProductVisibility(ProductVisibility $sourceProductVisibility)
    {
        $this->sourceProductVisibility = $sourceProductVisibility;

        return $this;
    }
}
