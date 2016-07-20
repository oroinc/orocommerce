<?php

namespace OroB2B\Bundle\CheckoutBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\ConfigField; // required by DatesAwareTrait
use Oro\Bundle\EntityBundle\EntityProperty\DatesAwareInterface;
use Oro\Bundle\EntityBundle\EntityProperty\DatesAwareTrait;
use Oro\Bundle\SecurityBundle\Tools\UUIDGenerator;

/**
 * @ORM\Entity(repositoryClass="OroB2B\Bundle\CheckoutBundle\Entity\Repository\CheckoutWorkflowStateRepository")
 * @ORM\Table(name="orob2b_checkout_workflow_state",
 *     uniqueConstraints={@ORM\UniqueConstraint(
 *         name="orob2b_checkout_workflow_state_unique_id_class_token_idx",
 *         columns={"entity_id", "entity_class", "token"}
 *     )}
 * )
 */
class CheckoutWorkflowState implements DatesAwareInterface
{
    use DatesAwareTrait;

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
     * @ORM\Column(type="string", name="token")
     */
    protected $token;

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

    public function __construct()
    {
        $this->setToken(UUIDGenerator::v4());
    }

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
    public function getToken()
    {
        return $this->token;
    }

    /**
     * @param string $token
     * @return $this
     */
    public function setToken($token)
    {
        $this->token = $token;

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
    public function setStateData(array $stateData)
    {
        $this->stateData = $stateData;

        return $this;
    }

    /**
     * @return int
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
        $this->entityId = (int)$entityId;

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
