<?php

namespace Oro\Bundle\PricingBundle\Model;

use Oro\Bundle\QueryDesignerBundle\Model\AbstractQueryDesigner;

class PriceListQueryDesigner extends AbstractQueryDesigner
{
    /**
     * @var string
     */
    protected $definition;

    /**
     * @var string
     */
    protected $entity;

    /**
     * {@inheritdoc}
     */
    public function getEntity()
    {
        return $this->entity;
    }

    /**
     * {@inheritdoc}
     */
    public function setEntity($entity)
    {
        $this->entity = $entity;
    }

    /**
     * {@inheritdoc}
     */
    public function getDefinition()
    {
        return $this->definition;
    }

    /**
     * {@inheritdoc}
     */
    public function setDefinition($definition)
    {
        $this->definition = $definition;
    }
}
