<?php

namespace OroB2B\Bundle\CheckoutBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="OroB2B\Bundle\CheckoutBundle\Entity\Repository\CheckoutWorkflowStateRepository")
 * @ORM\Table(name="oro_checkout_workflow_state",
 *     uniqueConstraints={@ORM\UniqueConstraint(name="unique_state", columns={"entity_id", "entity_class", "hash"})}
 * )
 */
class CheckoutWorkflowState
{
    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\Column(type="integer", name="id")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(type="string", name="hash", length=13)
     */
    protected $hash;

    /**
     * @var int
     *
     * @ORM\Column(type="integer", name="entity_id")
     */
    protected $entityId;

    /**
     * @var string
     *
     * @ORM\Column(type="string", name="entity_class", length=255)
     */
    protected $entityClass;

    /**
     * @var array
     *
     * @ORM\Column(name="state_data", type="array")
     */
    protected $stateData;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getHash()
    {
        return $this->hash;
    }

    /**
     * @param string $hash
     * @return $this
     */
    public function setHash($hash)
    {
        $this->hash = $hash;
        return $this;
    }

    /**
     * @return array
     */
    public function getStateData()
    {
        return $this->stateData;
    }

    /**
     * @param array $stateData
     * @return $this
     */
    public function setStateData($stateData)
    {
        $this->stateData = $stateData;
        return $this;
    }

    /**
     * @return integer
     */
    public function getEntityId()
    {
        return $this->entityId;
    }

    /**
     * @param int $entityId
     * @return $this
     */
    public function setEntityId($entityId)
    {
        $this->entityId = $entityId;
        return $this;
    }

    /**
     * @return string
     */
    public function getEntityClass()
    {
        return $this->entityClass;
    }

    /**
     * @param string $entityClass
     * @return $this
     */
    public function setEntityClass($entityClass)
    {
        $this->entityClass = $entityClass;
        return $this;
    }
}
