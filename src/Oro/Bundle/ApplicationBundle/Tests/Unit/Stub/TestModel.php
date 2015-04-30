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
     * @param string|null $first
     * @param string|null $second
     */
    public function __construct($first = null, $second = null)
    {
        $this->first = $first;
        $this->second = $second;

        parent::__construct(new \DateTime());
    }

    /**
     * {@inheritdoc}
     */
    public static function getModelName()
    {
        return 'test_model';
    }
}
