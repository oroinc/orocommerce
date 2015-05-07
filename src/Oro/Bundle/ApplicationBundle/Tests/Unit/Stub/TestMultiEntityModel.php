<?php

namespace Oro\Bundle\ApplicationBundle\Tests\Unit\Stub;

use Oro\Bundle\ApplicationBundle\Model\AbstractModel;

class TestMultiEntityModel extends AbstractModel
{
    /**
     * @var object
     */
    public $anotherEntity;

    /**
     * @param object $first
     * @param object $second
     */
    public function __construct($first, $second)
    {
        parent::__construct($first);

        $this->anotherEntity = $second;
    }

    /**
     * {@inheritdoc}
     */
    public static function getModelName()
    {
        return 'test_multi_entity_model';
    }

    /**
     * {@inheritdoc}
     */
    public function getEntities()
    {
        return [$this->entity, $this->anotherEntity];
    }
}
