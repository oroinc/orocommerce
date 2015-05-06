<?php

namespace OroB2B\Bundle\CustomerBundle\Model;

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
     * @var CustomerModel[]
     */
    protected $customers = [];

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
     * @return CustomerModel[]
     */
    public function getCustomers()
    {
        if (!$this->customers) {
            $this->entity->getCustomers()->map(
                function (AbstractCustomer $customer) {
                    $this->customers[] = $this->customerFactory->create([$customer]);
                }
            );
        }

        return $this->customers;
    }
}
