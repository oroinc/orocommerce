<?php

namespace OroB2B\Bundle\RFPAdminBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

use Gedmo\Mapping\Annotation as Gedmo;

use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;

use OroB2B\Bundle\ProductBundle\Entity\Product;

/**
 * @ORM\Table(name="orob2b_rfp_request_product")
 * @ORM\Entity
 * @Config(
 *      defaultValues={
 *          "entity"={
 *              "icon"="icon-list-alt"
 *          },
 *          "security"={
 *              "type"="ACL",
 *              "group_name"=""
 *          }
 *      }
 * )
 */
class RequestProduct
{
    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var Request
     *
     * @ORM\ManyToOne(targetEntity="Request", inversedBy="requestProducts")
     * @ORM\JoinColumn(name="request_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $request;

    /**
     * @var Product
     *
     * @ORM\ManyToOne(targetEntity="OroB2B\Bundle\ProductBundle\Entity\Product")
     * @ORM\JoinColumn(name="product_id", referencedColumnName="id", onDelete="SET NULL")
     */
    protected $product;

    /**
     * @var string
     *
     * @ORM\Column(name="product_sku", type="string", length=255)
     */
    protected $productSku;

    /**
     * @var string
     *
     * @ORM\Column(name="comment", type="text", nullable=true)
     */
    protected $comment;

    /**
     * @var Collection|RequestProductItem[]
     *
     * @ORM\OneToMany(targetEntity="RequestProductItem", mappedBy="requestProduct", cascade={"ALL"}, orphanRemoval=true)
     */
    protected $requestProductItems;


    /**
     * Constructor
     */
    public function __construct()
    {
        $this->requestProductItems = new ArrayCollection();
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set request
     *
     * @param Request $request
     * @return RequestProduct
     */
    public function setRequest(Request $request = null)
    {
        $this->request = $request;

        return $this;
    }

    /**
     * Get request
     *
     * @return Request
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * Set product
     *
     * @param Product $product
     * @return RequestProduct
     */
    public function setProduct(Product $product = null)
    {
        $this->product = $product;
        if ($product) {
            $this->productSku = $product->getSku();
        }

        return $this;
    }

    /**
     * Get product
     *
     * @return Product
     */
    public function getProduct()
    {
        return $this->product;
    }

    /**
     * Set productSku
     *
     * @param string $productSku
     * @return Product
     */
    public function setProductSku($productSku)
    {
        $this->productSku = $productSku;

        return $this;
    }

    /**
     * Get productSku
     *
     * @return string
     */
    public function getProductSku()
    {
        return $this->productSku;
    }

    /**
     * @param string $comment
     */
    public function setComment($comment)
    {
        $this->comment = $comment;
    }

    /**
     * @return string
     */
    public function getComment()
    {
        return $this->comment;
    }

    /**
     * Add requestProductItem
     *
     * @param RequestProductItem $requestProductItem
     * @return RequestProduct
     */
    public function addRequestProductItem(RequestProductItem $requestProductItem)
    {
        if (!$this->requestProductItems->contains($requestProductItem)) {
            $this->requestProductItems[] = $requestProductItem;
            $requestProductItem->setRequestProduct($this);
        }

        return $this;
    }

    /**
     * Remove requestProductItem
     *
     * @param RequestProductItem $requestProductItem
     * @return RequestProduct
     */
    public function removeRequestProductItem(RequestProductItem $requestProductItem)
    {
        $this->requestProductItems->removeElement($requestProductItem);

        return $this;
    }

    /**
     * Get requestProductItems
     *
     * @return Collection|RequestProductItem[]
     */
    public function getRequestProductItems()
    {
        return $this->requestProductItems;
    }
}
