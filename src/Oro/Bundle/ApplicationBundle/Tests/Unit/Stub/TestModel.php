<?php

namespace Oro\Bundle\ApplicationBundle\Tests\Unit\Stub;

use Oro\Bundle\ApplicationBundle\Model\AbstractModel;

class TestModel extends AbstractModel
{
    /**
     * @var string|null
     */
    public $first;

    /**
     * @var string|null
     */
    public $second;

    /**
     * @param object $entity
     * @param string|null $first
     * @param string|null $second
     */
    public function __construct($entity, $first = null, $second = null)
    {
        $this->first = $first;
        $this->second = $second;

        parent::__construct($entity);
    }

    /**
     * {@inheritdoc}
     */
    public static function getModelName()
    {
        return 'test_model';
    }
}
