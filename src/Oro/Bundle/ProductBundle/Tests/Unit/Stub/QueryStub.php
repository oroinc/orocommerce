<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Stub;

use Doctrine\ORM\AbstractQuery;

class QueryStub extends AbstractQuery
{
    /**
     * {@inheritdoc}
     */
    public function getSQL()
    {
    }

    /**
     * {@inheritdoc}
     * @codingStandardsIgnoreStart
     */
    protected function _doExecute()
    {
    }
    // @codingStandardsIgnoreEnd

    /**
     * @param integer|null $maxResults
     * @return $this
     */
    public function setMaxResults($maxResults)
    {
        return $this;
    }
}
