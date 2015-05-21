<?php

namespace Oro\Bundle\ApplicationBundle\Tests\Unit\Stub;

use Oro\Bundle\ApplicationBundle\Model\ModelInterface;

class TestCustomModel implements ModelInterface
{
    /**
     * {@inheritdoc}
     */
    public static function getModelName()
    {
        return 'test_custom_model';
    }

    /**
     * {@inheritdoc}
     */
    public function getEntities()
    {
        return [];
    }
}
