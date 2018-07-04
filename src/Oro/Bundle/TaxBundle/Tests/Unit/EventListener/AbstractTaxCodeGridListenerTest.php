<?php

namespace Oro\Bundle\TaxBundle\Tests\Unit\EventListener;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

abstract class AbstractTaxCodeGridListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var object */
    protected $listener;

    protected function setUp()
    {
        $this->listener = $this->createListener();
    }

    protected function tearDown()
    {
        unset($this->listener, $this->doctrineHelper);
    }

    /**
     * @return object
     */
    abstract protected function createListener();
}
