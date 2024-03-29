<?php

namespace Oro\Bundle\TaxBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\EntityBundle\EntityProperty\DatesAwareInterface;
use Oro\Bundle\EntityBundle\EntityProperty\DatesAwareTrait;
use Oro\Bundle\TaxBundle\Model\Result;

/**
 * Represents calculated taxes in database
 */
#[ORM\Entity]
#[ORM\Table(name: 'oro_tax_value')]
#[ORM\Index(columns: ['entity_class', 'entity_id'], name: 'oro_tax_value_class_id_idx')]
#[ORM\HasLifecycleCallbacks]
class TaxValue implements DatesAwareInterface
{
    use DatesAwareTrait;

    #[ORM\Id]
    #[ORM\Column(type: Types::INTEGER)]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    /**
     * @var Result
     */
    #[ORM\Column(name: 'result', type: 'json_array')]
    protected $result;

    #[ORM\Column(name: 'entity_class', type: Types::STRING, length: 255)]
    protected ?string $entityClass = null;

    #[ORM\Column(name: 'entity_id', type: Types::INTEGER, nullable: true)]
    protected ?int $entityId = null;

    #[ORM\Column(type: Types::TEXT)]
    protected ?string $address = null;

    public function __construct()
    {
        $this->result = new Result();
    }

    /**
     * Set entityId
     *
     * @param integer $entityId
     *
     * @return $this
     */
    public function setEntityId($entityId)
    {
        $this->entityId = $entityId;

        return $this;
    }

    /**
     * Get entityId
     *
     * @return integer
     */
    public function getEntityId()
    {
        return $this->entityId;
    }

    /**
     * Set address
     *
     * @param string $address
     *
     * @return $this
     */
    public function setAddress($address)
    {
        $this->address = $address;

        return $this;
    }

    /**
     * Get address
     *
     * @return string
     */
    public function getAddress()
    {
        return $this->address;
    }

    /**
     * Get entityClass
     *
     * @return string
     */
    public function getEntityClass()
    {
        return $this->entityClass;
    }

    /**
     * Set entityClass
     *
     * @param string $entityClass
     *
     * @return $this
     */
    public function setEntityClass($entityClass)
    {
        $this->entityClass = $entityClass;

        return $this;
    }

    /**
     * @param Result $result
     * @return $this
     */
    public function setResult(Result $result)
    {
        $this->result = $result;

        return $this;
    }

    /**
     * @return Result
     */
    public function getResult()
    {
        return $this->result;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    #[ORM\PostLoad]
    public function postLoad()
    {
        if (!$this->result instanceof Result) {
            $this->result = Result::jsonDeserialize($this->result);
        }
    }
}
