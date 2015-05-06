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
     * @param string $modelClassName
     * @param ContainerInterface $container
     */
    public function __construct($modelClassName, ContainerInterface $container)
    {
        parent::__construct($modelClassName);

        $this->customerFactory = $container->get('orob2b_customer_admin.factory.customer');
    }

    /**
     * {@inheritdoc}
     */
    public function create(array $arguments = [])
    {
        return new CustomerGroupModel(reset($arguments), $this->customerFactory);
    }
}
