<?php

namespace OroB2B\Bundle\CustomerBundle\Model;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use Oro\Bundle\ApplicationBundle\Factory\ModelFactoryInterface;
use Oro\Bundle\ApplicationBundle\Model\AbstractModel;

use OroB2B\Bundle\CustomerBundle\Entity\AbstractCustomer;
use OroB2B\Bundle\CustomerBundle\Entity\AbstractCustomerGroup;

class CustomerGroupModel extends AbstractModel
{
    /**
     * @var ModelFactoryInterface
     */
    protected $customerFactory;

    /**
     * @var AbstractCustomerGroup
     */
    protected $entity;

    /**
     * @var CustomerModel[]|Collection
     */
    protected $customers = false;

    /**
     * @var string
     */
    protected $name = false;

    /**
     * @param AbstractCustomerGroup $entity
     * @param ModelFactoryInterface $customerFactory
     */
    public function __construct(AbstractCustomerGroup $entity, ModelFactoryInterface $customerFactory)
    {
        parent::__construct($entity);

        $this->customerFactory = $customerFactory;
    }

    /**
     * {@inheritdoc}
     */
    public static function getModelName()
    {
        return 'customer_group';
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->entity->getId();
    }

    /**
     * @return CustomerModel[]|Collection
     */
    public function getCustomers()
    {
        if (false === $this->customers) {
            $this->customers = new ArrayCollection();

            $this->entity->getCustomers()->map(
                function (AbstractCustomer $customer) {
                    $model = $this->customerFactory->create([$customer]);
                    if (!$this->customers->contains($model)) {
                        $this->customers->add($model);
                    }
                }
            );
        }

        return $this->customers;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->entity->getName();
    }

    /**
     * @param string $name
     * @return $this
     */
    public function setName($name)
    {
        $this->entity->setName($name);

        return $this;
    }
}
