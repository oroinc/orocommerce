<?php

namespace OroB2B\Bundle\RFPAdminBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\ConfigField;

use OroB2B\Bundle\RFPAdminBundle\Model\ExtendRequest;

/**
 * Request
 *
 * @ORM\Table("orob2b_rfp_request")
 * @ORM\Entity
 * @Config(
 *      routeName="orob2b_rfp_admin_request_index",
 *      routeView="orob2b_rfp_admin_request_view",
 *      defaultValues={
 *          "entity"={
 *              "icon"="icon-file-text"
 *          },
 *          "security"={
 *              "type"="ACL",
 *              "group_name"=""
 *          },
 *          "grouping"={"groups"={"activity"}}
 *      }
 * )
 * @ORM\HasLifecycleCallbacks()
 */
class Request extends ExtendRequest
{
    /**
     * @var RequestStatus
     * @ORM\ManyToOne(targetEntity="OroB2B\Bundle\RFPAdminBundle\Entity\RequestStatus")
     * @ORM\JoinColumn(name="status_id", referencedColumnName="id", onDelete="SET NULL")
     */
    protected $status;

    /**
     * @var Collection|RequestProduct[]
     *
     * @ORM\OneToMany(targetEntity="RequestProduct", mappedBy="request", cascade={"ALL"}, orphanRemoval=true)
     */
    protected $requestProducts;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created_at", type="datetime")
     * @ConfigField(
     *      defaultValues={
     *          "entity"={
     *              "label"="oro.ui.created_at"
     *          }
     *      }
     * )
     */
    protected $createdAt;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="updated_at", type="datetime")
     * @ConfigField(
     *      defaultValues={
     *          "entity"={
     *              "label"="oro.ui.updated_at"
     *          }
     *      }
     * )
     */
    protected $updatedAt;

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();

        $this->createdAt  = new \DateTime('now', new \DateTimeZone('UTC'));
        $this->updatedAt  = new \DateTime('now', new \DateTimeZone('UTC'));
        $this->requestProducts = new ArrayCollection();
    }
    
    
    /**
     * Add requestProducts
     *
     * @param RequestProduct $requestProduct
     * @return Request
     */
    public function addRequestProduct(RequestProduct $requestProduct)
    {
        if (!$this->requestProducts->contains($requestProduct)) {
            $this->requestProducts[] = $requestProduct;
            $requestProduct->setRequest($this);
        }

        return $this;
    }

    /**
     * Remove requestProducts
     *
     * @param RequestProduct $requestProduct
     */
    public function removeRequestProduct(RequestProduct $requestProduct)
    {
        if ($this->requestProducts->contains($requestProduct)) {
            $this->requestProducts->removeElement($requestProduct);
        }
    }

    /**
     * Get requestProducts
     *
     * @return Collection|RequestProduct[]
     */
    public function getRequestProducts()
    {
        return $this->requestProducts;
    }
}
