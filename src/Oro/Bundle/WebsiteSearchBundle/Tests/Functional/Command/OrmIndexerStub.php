<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Functional\Command;

class OrmIndexerStub
{
    /**
     * @param null $class
     * @param array $context
     * @return int
     */
    public function reindex($class = null, $context = [])
    {
        return 12;
    }
}
