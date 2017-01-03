<?php
namespace Oro\Bundle\FrontendBundle\Tests\Unit\EventListener;

use Oro\Bundle\ActionBundle\Provider\CurrentApplicationProviderInterface;

use Oro\Bundle\FrontendBundle\Provider\ActionCurrentApplicationProvider;
use Oro\Bundle\FrontendBundle\Extension\FrontendTransitionButtonProviderExtension;

use Oro\Bundle\WorkflowBundle\Tests\Unit\Extension\TransitionButtonProviderExtensionTest;

class FrontendTransitionButtonProviderExtensionTest extends TransitionButtonProviderExtensionTest
{
    /** @var FrontendTransitionButtonProviderExtension */
    protected $extension;

    /** @var CurrentApplicationProviderInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $applicationProvider;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->applicationProvider = $this->getMockBuilder(CurrentApplicationProviderInterface::class)
            ->disableOriginalConstructor()->getMock();

        $this->extension = new FrontendTransitionButtonProviderExtension(
            $this->workflowRegistry,
            $this->routeProvider
        );

        $this->extension->setApplicationProvider($this->applicationProvider);
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        parent::tearDown();
        unset($this->applicationProvider);
    }

    /**
     * @dataProvider findDataProvider
     *
     * @param bool $expected
     * @param null $entityClass
     * @param null $datagrid
     */
    public function testFind($expected, $entityClass = null, $datagrid = null)
    {
        if ($expected) {
            $this->applicationProvider->expects($this->atLeastOnce())
                ->method('getCurrentApplication')
                ->willReturn(ActionCurrentApplicationProvider::COMMERCE_APPLICATION);
        }
        parent::testFind($expected, $entityClass, $datagrid);
    }
}
