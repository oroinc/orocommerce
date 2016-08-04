<?php

namespace OroB2B\Bundle\ProductBundle\Tests\Unit\EventListener;

use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

use OroB2B\Bundle\ProductBundle\EventListener\ScopedProductSelectDBQueryEventListener;

class ScopedProductSelectDBQueryEventListenerTest extends ProductSelectDBQueryEventListenerTest
{
    const SCOPE = 'test_scope';

    /**
     * @var ScopedProductSelectDBQueryEventListener
     */
    protected $listener;

    /**
     * @return ScopedProductSelectDBQueryEventListener
     */
    protected function createListener()
    {
        $requestStack = new RequestStack();
        $requestStack->push(new Request());
        $listener = new ScopedProductSelectDBQueryEventListener(
            $this->configManager,
            $this->modifier,
            $this->frontendHelper,
            $requestStack
        );
        $listener->setScope(self::SCOPE);

        return $listener;
    }

    /**
     * @dataProvider onQueryDataProvider
     * @param bool $isFrontend
     * @param string|null $frontendPath
     * @param string|null $backendPath
     */
    public function testOnQuery($isFrontend, $frontendPath, $backendPath)
    {
        $this->event->expects($this->any())
            ->method('getDataParameters')
            ->willReturn(new ParameterBag(['scope' => self::SCOPE]));

        parent::testOnQuery($isFrontend, $frontendPath, $backendPath);
    }

    public function testOnQueryWrongScope()
    {
        $this->modifier->expects($this->never())
            ->method($this->anything());

        $this->listener->setScope(self::SCOPE);
        $this->listener->setBackendSystemConfigurationPath('path');

        $this->event->expects($this->once())
            ->method('getDataParameters')
            ->willReturn(new ParameterBag(['scope' => 'wrong_scope']));

        $this->listener->onDBQuery($this->event);
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Scope not configured for ProductSelectDBQueryEventListener
     */
    public function testScopeEmpty()
    {
        $this->listener->setBackendSystemConfigurationPath('path');
        $this->listener->setScope(null);

        $this->listener->onDBQuery($this->event);
    }
}
