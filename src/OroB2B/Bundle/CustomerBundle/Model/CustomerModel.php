<?php

namespace OroB2B\Bundle\CustomerBundle\Model;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

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
    protected $parent = false;

    /**
     * @var CustomerModel[]|Collection
     */
    protected $children = false;

    /**
     * @var CustomerGroupModel
     */
    protected $group = false;

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
     * @return int
     */
    public function getId()
    {
        return $this->entity->getId();
    }

    /**
     * @return CustomerModel
     */
    public function getParent()
    {
        if (false === $this->parent) {
            $parent = $this->entity->getParent();

            $this->parent = null;
            if ($parent) {
                $this->parent = $this->customerFactory->create([$parent]);
            }
        }

        return $this->parent;
    }

    /**
     * @return CustomerModel[]|Collection
     */
    public function getChildren()
    {
        if (false === $this->children) {
            $this->children = new ArrayCollection();

            $this->entity->getChildren()->map(
                function (AbstractCustomer $customer) {
                    $model = $this->customerFactory->create([$customer]);
                    if (!$this->children->contains($model)) {
                        $this->children->add($model);
                    }
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
        if (false === $this->group) {
            $group = $this->entity->getGroup();

            $this->group = null;
            if ($group) {
                $this->group = $this->groupFactory->create([$group]);
            }
        }

        return $this->group;
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
