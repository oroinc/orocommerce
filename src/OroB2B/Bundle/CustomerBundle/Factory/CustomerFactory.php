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

        return parent::create([$entity, $this->getGroupFactory(), $this]);
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
