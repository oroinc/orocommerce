<?php

namespace Oro\Bundle\ApplicationBundle\Tests\Unit\Factory\Stub;

use Oro\Bundle\ApplicationBundle\Model\ModelInterface;

class TestModel implements ModelInterface
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
     * @param string|null $first
     * @param string|null $second
     */
    public function __construct($first = null, $second = null)
    {
        $this->first = $first;
        $this->second = $second;
    }

    /**
     * {@inheritdoc}
     */
    public function getEntities()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public static function getModelName()
    {
        return 'test';
    }
}
