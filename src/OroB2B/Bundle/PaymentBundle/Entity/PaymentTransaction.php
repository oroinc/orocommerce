<?php

namespace OroB2B\Bundle\PaymentBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="orob2b_payment_transaction")
 * @ORM\Entity
 */
class PaymentTransaction
{
    /**
     * @var int
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string
     * @ORM\Column(name="reference", type="string")
     */
    protected $reference;

    /**
     * @var string
     * @ORM\Column(name="state", type="string", nullable=true)
     */
    protected $state;

    /**
     * @var string
     * @ORM\Column(name="type", type="string")
     */
    protected $type;

    /**
     * @var string
     * @ORM\Column(name="entity_class", type="string")
     */
    protected $entityClass;

    /**
     * @var int
     * @ORM\Column(name="entity_identifier", type="integer")
     */
    protected $entityIdentifier;

    /**
     * @var string
     * @ORM\Column(name="data", type="text", nullable=true)
     */
    protected $data;

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
    public function getReference()
    {
        return $this->reference;
    }

    /**
     * @param string $reference
     * @return PaymentTransaction
     */
    public function setReference($reference)
    {
        $this->reference = $reference;

        return $this;
    }

    /**
     * @return string
     */
    public function getState()
    {
        return $this->state;
    }

    /**
     * @param string $state
     * @return PaymentTransaction
     */
    public function setState($state)
    {
        $this->state = $state;

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
     * @return PaymentTransaction
     */
    public function setType($type)
    {
        $this->type = (string)$type;

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
     * @return PaymentTransaction
     */
    public function setEntityClass($entityClass)
    {
        $this->entityClass = (string)$entityClass;

        return $this;
    }

    /**
     * @return string
     */
    public function getEntityIdentifier()
    {
        return $this->entityIdentifier;
    }

    /**
     * @param int $entityIdentifier
     * @return PaymentTransaction
     */
    public function setEntityIdentifier($entityIdentifier)
    {
        $this->entityIdentifier = (int)$entityIdentifier;

        return $this;
    }

    /**
     * @return string
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @param string $data
     * @return PaymentTransaction
     */
    public function setData($data)
    {
        $this->data = (string)$data;

        return $this;
    }
}
