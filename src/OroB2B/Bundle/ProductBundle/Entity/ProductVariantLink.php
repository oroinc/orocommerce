<?php

namespace OroB2B\Bundle\ProductBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\ConfigField;

/**
 * @ORM\Entity
 * @ORM\Table(name="orob2b_product_variant_link")
 */
class ProductVariantLink
{
    /**
     * @var integer
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var Product
     * @ORM\ManyToOne(targetEntity="Product", inversedBy="variantLinks")
     * @ORM\JoinColumn(name="product_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $product;

    /**
     * @var Product
     * @ORM\OneToOne(targetEntity="Product")
     * @ORM\JoinColumn(name="variant_id", referencedColumnName="id")
     */
    protected $variant;

    /**
     * @var boolean
     * @ORM\Column(name="linked", type="boolean", options={"default"="1"})
     */
    protected $linked;
}
