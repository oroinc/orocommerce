<?php

namespace OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved;

use Doctrine\ORM\Mapping as ORM;

use OroB2B\Bundle\AccountBundle\Entity\Visibility\ProductVisibility;
use OroB2B\Bundle\AccountBundle\Entity\Visibility\VisibilityInterface;

/**
 * @ORM\Entity
 * @ORM\Table(name="orob2b_prod_vsb_resolv")
 */
class ProductVisibilityResolved extends BaseProductVisibilityResolved
{
    /**
     * @var ProductVisibility
     *
     * @ORM\ManyToOne(targetEntity="OroB2B\Bundle\AccountBundle\Entity\Visibility\ProductVisibility")
     * @ORM\JoinColumn(name="source_product_visibility", referencedColumnName="id", onDelete="SET NULL", nullable=true)
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
     * @param VisibilityInterface|ProductVisibility|null $sourceProductVisibility
     * @return $this
     */
    public function setSourceProductVisibility(VisibilityInterface $sourceProductVisibility = null)
    {
        $this->sourceProductVisibility = $sourceProductVisibility;

        return $this;
    }
}
