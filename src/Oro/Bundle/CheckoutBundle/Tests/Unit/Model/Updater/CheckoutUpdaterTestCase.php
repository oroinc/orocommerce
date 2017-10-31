<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Model\Updater;

use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\WorkflowData\WorkflowDataUpdaterInterface;
use Oro\Component\Testing\Unit\EntityTrait;

abstract class CheckoutUpdaterTestCase extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /** @var WorkflowDataUpdaterInterface */
    protected $updater;

    /**
     * @dataProvider isApplicableDataProvider
     *
     * @param array $recordGroups
     * @param mixed $source
     * @param bool $expected
     */
    public function testIsApplicable(array $recordGroups, $source, $expected)
    {
        /** @var WorkflowDefinition $workflowDefinition */
        $workflowDefinition = $this->getEntity(WorkflowDefinition::class, ['exclusiveRecordGroups' => $recordGroups]);

        $this->assertSame($expected, $this->updater->isApplicable($workflowDefinition, $source));
    }


    /**
     * @return array
     */
    public function isApplicableDataProvider()
    {
        $order = new Order();

        return [
            'no groups' => [
                'recordGroups' => [],
                'source' => $order,
                'expected' => false,
            ],
            'wrong groups' => [
                'recordGroups' => ['test_group'],
                'source' => $order,
                'expected' => false,
            ],
            'no source' => [
                'recordGroups' => ['b2b_checkout_flow'],
                'source' => null,
                'expected' => false,
            ],
            'wrong source' => [
                'recordGroups' => ['b2b_checkout_flow'],
                'source' => new \stdClass(),
                'expected' => false,
            ],
            'right data' => [
                'recordGroups' => ['test_group', 'b2b_checkout_flow'],
                'source' => $order,
                'expected' => true,
            ],
        ];
    }
}
