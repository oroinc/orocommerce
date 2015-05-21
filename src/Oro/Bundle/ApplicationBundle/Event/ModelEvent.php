<?php

namespace Oro\Bundle\ApplicationBundle\Event;

use Symfony\Component\EventDispatcher\Event;

use Oro\Bundle\ApplicationBundle\Model\ModelInterface;

class ModelEvent extends Event
{
    /**
     * @var ModelInterface
     */
    protected $model;

    /**
     * @param ModelInterface $model
     */
    public function __construct(ModelInterface $model)
    {
        $this->setModel($model);
    }

    /**
     * @return ModelInterface
     */
    public function getModel()
    {
        return $this->model;
    }

    /**
     * @param ModelInterface $model
     */
    public function setModel(ModelInterface $model)
    {
        $this->model = $model;
    }
}
