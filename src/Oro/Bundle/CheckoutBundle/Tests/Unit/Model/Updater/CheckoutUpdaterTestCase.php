<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Model\Updater;

use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\WorkflowData\WorkflowDataUpdaterInterface;

abstract class CheckoutUpdaterTestCase extends \PHPUnit_Framework_TestCase
{
    /** @var WorkflowDataUpdaterInterface */
    protected $updater;

    public function testIsApplicableUnsupportedWorkflow()
    {
        $this->assertFalse($this->updater->isApplicable(new WorkflowDefinition(), new Order()));
    }

    public function testIsApplicableUnsupportedSource()
    {
        $workflow = new WorkflowDefinition();
        $workflow->setExclusiveRecordGroups(['b2b_checkout_flow']);

        $this->assertFalse($this->updater->isApplicable($workflow, new \stdClass()));
    }

    public function testIsApplicable()
    {
        $workflow = new WorkflowDefinition();
        $workflow->setExclusiveRecordGroups(['b2b_checkout_flow']);

        $this->assertTrue($this->updater->isApplicable($workflow, new Order()));
    }
}
