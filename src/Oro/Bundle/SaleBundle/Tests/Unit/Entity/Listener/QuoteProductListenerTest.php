<?php

namespace Oro\Bundle\SaleBundle\Tests\Unit\Entity\Listener;

use Doctrine\ORM\Event\PreUpdateEventArgs;
use Oro\Bundle\SaleBundle\Entity\Listener\QuoteProductListener;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class QuoteProductListenerTest extends \PHPUnit_Framework_TestCase
{
    /** @var  \PHPUnit_Framework_MockObject_MockObject|RequestStack $listener*/
    private $requestStack;

    /** @var  \PHPUnit_Framework_MockObject_MockObject|Request $listener*/
    private $request;

    /** @var  \PHPUnit_Framework_MockObject_MockObject|PreUpdateEventArgs $event*/
    private $event;

    /** @var  QuoteProductListener $listener*/
    private $listener;

    protected function setUp()
    {
        $this->requestStack =  $this->getMockBuilder(RequestStack::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->request =  $this->getMockBuilder(Request::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->event = $this->getMockBuilder(PreUpdateEventArgs::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->listener = new QuoteProductListener($this->requestStack);
    }

    /**
     * @param string$route
     * @param  boolean$restoreValue
     *
     * @dataProvider preUpdateProvider
     */
    public function testPreUpdate($route, $restoreValue)
    {
        $fieldToKeep = 'commentCustomer';

        $this->request->expects($this->once())
            ->method('get')
            ->with('_route')
            ->willReturn($route);

        $this->requestStack->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn($this->request);

        $this->event->expects($this->exactly((int)$restoreValue))
            ->method('hasChangedField')
            ->with($fieldToKeep)
            ->willReturn(true);

        $this->event->expects($this->exactly((int)$restoreValue))
            ->method('setNewValue')
            ->with($fieldToKeep);

        $this->listener->preUpdate($this->event);
    }

    /**
     * @return array
     */
    public function preUpdateProvider()
    {
        return [
            'admin page' => [
                'route' => 'oro_sale_quote_update',
                'restoreValue' => true
            ],
            'user page' => [
                'route' => 'oro_rfp_frontend_update',
                'restoreValue' => false
            ]
        ];
    }
}
