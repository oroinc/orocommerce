<?php

namespace Oro\Bundle\SaleBundle\Tests\Unit\Entity\Listener;

use Doctrine\ORM\Event\PreUpdateEventArgs;
use Oro\Bundle\SaleBundle\Entity\Listener\QuoteProductListener;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class QuoteProductListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|RequestStack */
    private $requestStack;

    /** @var \PHPUnit\Framework\MockObject\MockObject|Request */
    private $request;

    /** @var \PHPUnit\Framework\MockObject\MockObject|PreUpdateEventArgs */
    private $event;

    /** @var QuoteProductListener */
    private $listener;

    protected function setUp(): void
    {
        $this->requestStack =  $this->createMock(RequestStack::class);
        $this->request =  $this->createMock(Request::class);
        $this->event = $this->createMock(PreUpdateEventArgs::class);

        $this->listener = new QuoteProductListener($this->requestStack);
    }

    /**
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

    public function preUpdateProvider(): array
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
