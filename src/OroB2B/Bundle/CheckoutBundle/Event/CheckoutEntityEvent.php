<?php

namespace OroB2B\Bundle\CheckoutBundle\Event;

use Symfony\Component\EventDispatcher\Event;

use OroB2B\Bundle\CheckoutBundle\Entity\CheckoutInterface;
use OroB2B\Bundle\CheckoutBundle\Entity\CheckoutSource;

class CheckoutEntityEvent extends Event
{
    /**
     * @var CheckoutInterface
     */
    protected $checkoutEntity;

    /**
     * @var object
     */
    protected $source;

    /**
     * @var int
     */
    protected $checkoutId;

    /**
     * @var string
     */
    protected $type;

    /**
     * @var string
     */
    protected $workflowName;

    /**
     * @return CheckoutInterface
     */
    public function getCheckoutEntity()
    {
        return $this->checkoutEntity;
    }

    /**
     * @param CheckoutInterface $checkoutEntity
     */
    public function setCheckoutEntity(CheckoutInterface $checkoutEntity = null)
    {
        $this->checkoutEntity = $checkoutEntity;
    }

    /**
     * @return CheckoutSource
     */
    public function getSource()
    {
        return $this->source;
    }

    /**
     * @param CheckoutSource $source
     */
    public function setSource($source)
    {
        $this->source = $source;
    }

    /**
     * @return int
     */
    public function getCheckoutId()
    {
        return $this->checkoutId;
    }

    /**
     * @param int $checkoutId
     * @return $this
     */
    public function setCheckoutId($checkoutId)
    {
        $this->checkoutId = $checkoutId;
        return $this;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param string $type
     * @return $this
     */
    public function setType($type)
    {
        $this->type = $type;
        return $this;
    }

    /**
     * @return string
     */
    public function getWorkflowName()
    {
        return $this->workflowName;
    }

    /**
     * @param string $workflowName
     * @return $this
     */
    public function setWorkflowName($workflowName)
    {
        $this->workflowName = $workflowName;
        return $this;
    }
}
