<?php

namespace OroB2B\Component\Duplicator\Test\Stub;

use Doctrine\Common\Collections\ArrayCollection;

class RFPRequest
{
    /**
     * @var int
     */
    protected $id;

    /**
     * @var string
     */
    protected $email;

    /**
     * @var Status
     */
    protected $status;

    /**
     * @var ArrayCollection
     */
    protected $requestProducts;

    /**
     * @var \DateTime
     */
    protected $createdAt;

    /**
     * @param int $id
     */
    public function __construct($id)
    {
        $this->requestProducts = new ArrayCollection();
        $this->createdAt = new \DateTime();
        $this->id = $id;
    }

    /**
     * @return id
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @param string $email
     */
    public function setEmail($email)
    {
        $this->email = $email;
    }

    /**
     * @return Status
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param Status $status
     */
    public function setStatus(Status $status)
    {
        $this->status = $status;
    }

    /**
     * @return ArrayCollection|RequestProduct[]
     */
    public function getRequestProducts()
    {
        return $this->requestProducts;
    }

    /**
     * @param RequestProduct $requestProducts
     */
    public function addRequestProduct(RequestProduct $requestProducts)
    {
        $this->requestProducts->add($requestProducts);
    }

    /**
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * @param \DateTime $createdAt
     */
    public function setCreatedAt(\DateTime $createdAt)
    {
        $this->createdAt = $createdAt;
    }
}
