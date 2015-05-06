<?php

namespace OroB2B\Bundle\CustomerBundle\Model;

use Oro\Bundle\ApplicationBundle\Factory\ModelFactoryInterface;
use Oro\Bundle\ApplicationBundle\Model\AbstractModel;

use OroB2B\Bundle\CustomerBundle\Entity\AbstractCustomer;

class CustomerModel extends AbstractModel
{
    /**
     * @var ModelFactoryInterface
     */
    protected $groupFactory;

    /**
     * @var ModelFactoryInterface
     */
    protected $customerFactory;

    /**
     * @var AbstractCustomer
     */
    protected $entity;

    /**
     * @var CustomerModel
     */
    protected $parent;

    /**
     * @var CustomerModel[]
     */
    protected $children;

    /**
     * @var CustomerGroupModel
     */
    protected $group;

    /**
     * @param AbstractCustomer $entity
     * @param ModelFactoryInterface $groupFactory
     * @param ModelFactoryInterface $customerFactory
     */
    public function __construct(
        AbstractCustomer $entity,
        ModelFactoryInterface $groupFactory,
        ModelFactoryInterface $customerFactory
    ) {
        parent::__construct($entity);

        $this->groupFactory = $groupFactory;
        $this->customerFactory = $customerFactory;
    }

    /**
     * {@inheritdoc}
     */
    public static function getModelName()
    {
        return 'customer';
    }

    /**
     * @return CustomerModel
     */
    public function getParent()
    {
        if (!$this->parent) {
            $this->parent = $this->customerFactory->create([$this->entity->getParent()]);
        }

        return $this->parent;
    }

    /**
     * @return CustomerModel[]
     */
    public function getChildren()
    {
        if (!$this->children) {
            $this->entity->getChildren()->map(
                function (AbstractCustomer $customer) {
                    $this->children[] = $this->customerFactory->create([$customer]);
                }
            );
        }

        return $this->children;
    }

    /**
     * @return CustomerGroupModel
     */
    public function getGroup()
    {
        if (!$this->group) {
            $this->group = $this->groupFactory->create([$this->entity->getGroup()]);
        }

        return $this->group;
    }
}
