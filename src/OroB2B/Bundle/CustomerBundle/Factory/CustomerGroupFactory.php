<?php

namespace OroB2B\Bundle\CustomerBundle\Factory;

use Symfony\Component\DependencyInjection\ContainerInterface;

use Oro\Bundle\ApplicationBundle\Factory\ModelFactory;
use Oro\Bundle\ApplicationBundle\Factory\ModelFactoryInterface;

use OroB2B\Bundle\CustomerBundle\Model\CustomerGroupModel;

class CustomerGroupFactory extends ModelFactory
{
    /**
     * @var ModelFactoryInterface
     */
    protected $customerFactory;

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @param string $modelClassName
     * @param ContainerInterface $container
     */
    public function __construct($modelClassName, ContainerInterface $container)
    {
        parent::__construct($modelClassName);

        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function create(array $arguments = [])
    {
        return new CustomerGroupModel(reset($arguments), $this->getCustomerFactory());
    }

    /**
     * @return ModelFactoryInterface
     */
    protected function getCustomerFactory()
    {
        if (!$this->customerFactory) {
            $this->customerFactory = $this->container->get('orob2b_customer_admin.factory.customer');
        }

        return $this->customerFactory;
    }
}
