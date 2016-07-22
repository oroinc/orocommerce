<?php

namespace OroB2B\Bundle\CheckoutBundle\Tests\Unit\Layout\DataProvider;

use Doctrine\Common\Collections\ArrayCollection;

use Oro\Bundle\WorkflowBundle\Model\Transition;
use Oro\Component\Layout\ContextInterface;

use OroB2B\Bundle\CheckoutBundle\Layout\DataProvider\BackTransitionDataProvider;
use OroB2B\Bundle\CheckoutBundle\Model\TransitionData;

class BackTransitionDataProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\stdClass
     */
    protected $transitionsDataProvider;

    /**
     * @var BackTransitionDataProvider
     */
    protected $dataProvider;

    protected function setUp()
    {
        $this->transitionsDataProvider = $this->getMock(\stdClass::class, ['getData']);
        $this->dataProvider = new BackTransitionDataProvider();
        $this->dataProvider->setBackTransitionsDataProvider($this->transitionsDataProvider);
    }

    /**
     * @dataProvider transitionsDataProvider
     * @param array $transitions
     * @param mixed $expected
     */
    public function testGetData(array $transitions, $expected)
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|ContextInterface $context */
        $context = $this->getMock('Oro\Component\Layout\ContextInterface');

        $this->transitionsDataProvider->expects($this->once())
            ->method('getData')
            ->with($context)
            ->will($this->returnValue($transitions));

        $this->assertEquals($expected, $this->dataProvider->getData($context));
    }

    /**
     * @return array
     */
    public function transitionsDataProvider()
    {
        $transition = new Transition();
        $transitionOne = new TransitionData($transition, true, new ArrayCollection());
        $transitionTwo = new TransitionData($transition, true, new ArrayCollection());

        return [
            [
                [], null
            ],
            [
                [
                    $transitionOne,
                    $transitionTwo
                ],
                $transitionTwo
            ]
        ];
    }
}
