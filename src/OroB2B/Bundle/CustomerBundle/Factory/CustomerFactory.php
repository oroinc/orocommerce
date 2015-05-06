<?php

namespace OroB2B\Bundle\CustomerBundle\Factory;

use Symfony\Component\DependencyInjection\ContainerInterface;

use Oro\Bundle\ApplicationBundle\Factory\ModelFactory;
use Oro\Bundle\ApplicationBundle\Factory\ModelFactoryInterface;

use OroB2B\Bundle\CustomerBundle\Model\CustomerModel;

class CustomerFactory extends ModelFactory
{
    /**
     * @var ModelFactoryInterface
     */
    protected $groupFactory;

    /**
     * @param string $modelClassName
     * @param ContainerInterface $container
     */
    public function __construct($modelClassName, ContainerInterface $container)
    {
        parent::__construct($modelClassName);

        $this->groupFactory = $container->get('orob2b_customer_admin.factory.customer_group');
    }

    /**
     * {@inheritdoc}
     */
    public function create(array $arguments = [])
    {
        return new CustomerModel(reset($arguments), $this->groupFactory, $this);
    }
}
