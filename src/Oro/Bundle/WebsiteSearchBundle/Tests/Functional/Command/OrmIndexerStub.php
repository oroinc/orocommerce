<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Functional\Command;

class OrmIndexerStub
{
    /**
     * @param string|null $class
     * @param array $context
     * @return int
     */
    public function reindex($class = null, array $context = [])
    {
        return 12;
    }
}
