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
     * @param string $entityClassName
     * @param ContainerInterface $container
     */
    public function __construct($modelClassName, $entityClassName, ContainerInterface $container)
    {
        parent::__construct($modelClassName, $entityClassName);

        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function create(array $arguments = [])
    {
        $entity = null;
        if (!empty($arguments[0])) {
            $entity = $arguments[0];
        }

        return parent::create([$entity, $this->getCustomerFactory()]);
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
