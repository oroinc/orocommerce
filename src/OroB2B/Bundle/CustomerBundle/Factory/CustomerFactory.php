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
        return new CustomerModel(reset($arguments), $this->getGroupFactory(), $this);
    }

    /**
     * @return ModelFactoryInterface
     */
    protected function getGroupFactory()
    {
        if (!$this->groupFactory) {
            $this->groupFactory = $this->container->get('orob2b_customer_admin.factory.customer_group');
        }

        return $this->groupFactory;
    }
}
