<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Layout\DataProvider\MultiShipping;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\CheckoutBundle\Layout\DataProvider\MultiShipping\ContinueTransitionButtonDataProvider;
use Oro\Bundle\CheckoutBundle\Layout\DataProvider\TransitionProvider;
use Oro\Bundle\CheckoutBundle\Model\TransitionData;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Model\Transition;
use Oro\Bundle\WorkflowBundle\Resolver\TransitionOptionsResolver;

class ContinueTransitionButtonDataProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var TransitionProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $transitionProvider;

    /** @var ContinueTransitionButtonDataProvider */
    private $provider;

    protected function setUp(): void
    {
        $this->transitionProvider = $this->createMock(TransitionProvider::class);

        $this->provider = new ContinueTransitionButtonDataProvider($this->transitionProvider);
    }

    public function testGetContinueTransition()
    {
        $transitionOptionsResolver = $this->createMock(TransitionOptionsResolver::class);
        $transition = new Transition($transitionOptionsResolver);
        $transition->setFrontendOptions([]);

        $transitionData = new TransitionData($transition, true, new ArrayCollection());

        $this->transitionProvider->expects($this->once())
            ->method('getContinueTransition')
            ->willReturn($transitionData);

        $this->provider->getContinueTransition(new WorkflowItem());

        $expectedFrontendOptions = $transition->getFrontendOptions();

        $this->assertNotEmpty($expectedFrontendOptions);
        $this->arrayHasKey('page_component_module');
        $this->assertEquals(
            'orocheckout/js/app/components/multi-shipping-transition-button-component',
            $expectedFrontendOptions['page_component_module']
        );
    }
}
