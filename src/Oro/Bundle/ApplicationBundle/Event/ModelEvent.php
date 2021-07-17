<?php

namespace Oro\Bundle\ApplicationBundle\Event;

use Oro\Bundle\ApplicationBundle\Model\ModelInterface;
use Symfony\Contracts\EventDispatcher\Event;

class ModelEvent extends Event
{
    /**
     * @var ModelInterface
     */
    protected $model;

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

    public function setModel(ModelInterface $model)
    {
        $this->model = $model;
    }
}
