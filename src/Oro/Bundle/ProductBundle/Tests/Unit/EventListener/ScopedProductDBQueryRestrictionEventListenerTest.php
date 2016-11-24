<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\EventListener;

use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

use Oro\Bundle\ProductBundle\EventListener\ScopedProductDBQueryRestrictionEventListener;

class ScopedProductDBQueryRestrictionEventListenerTest extends ProductDBQueryRestrictionEventListenerTest
{
    const SCOPE = 'test_scope';

    /**
     * @var ScopedProductDBQueryRestrictionEventListener
     */
    protected $listener;

    /**
     * @return ScopedProductDBQueryRestrictionEventListener
     */
    protected function createListener()
    {
        $requestStack = new RequestStack();
        $requestStack->push(new Request());
        $listener = new ScopedProductDBQueryRestrictionEventListener(
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
     * @expectedExceptionMessage Scope not configured for ProductDBQueryRestrictionEventListener
     */
    public function testScopeEmpty()
    {
        $this->listener->setBackendSystemConfigurationPath('path');
        $this->listener->setScope(null);

        $this->listener->onDBQuery($this->event);
    }
}
