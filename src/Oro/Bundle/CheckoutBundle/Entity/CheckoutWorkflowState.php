<?php

namespace Oro\Bundle\CheckoutBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\CheckoutBundle\Entity\Repository\CheckoutWorkflowStateRepository;
use Oro\Bundle\EntityBundle\EntityProperty\DatesAwareInterface;
use Oro\Bundle\EntityBundle\EntityProperty\DatesAwareTrait;
use Oro\Bundle\SecurityBundle\Tools\UUIDGenerator;

/**
 * Store Checkout Workflow State in a database
 */
#[ORM\Entity(repositoryClass: CheckoutWorkflowStateRepository::class)]
#[ORM\Table(name: 'oro_checkout_workflow_state')]
#[ORM\UniqueConstraint(name: 'oro_checkout_wf_state_uidx', columns: ['entity_id', 'entity_class', 'token'])]
class CheckoutWorkflowState implements DatesAwareInterface
{
    use DatesAwareTrait;

    #[ORM\Id]
    #[ORM\Column(name: 'id', type: Types::INTEGER)]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[ORM\Column(name: 'token', type: Types::STRING)]
    protected ?string $token = null;

    #[ORM\Column(name: 'entity_id', type: Types::INTEGER)]
    protected ?int $entityId = null;

    #[ORM\Column(name: 'entity_class', type: Types::STRING, length: 255)]
    protected ?string $entityClass = null;

    /**
     * @var array
     */
    #[ORM\Column(name: 'state_data', type: Types::ARRAY)]
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
