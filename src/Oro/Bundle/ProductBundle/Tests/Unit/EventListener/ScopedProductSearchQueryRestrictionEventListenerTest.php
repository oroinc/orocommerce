<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\EventListener;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

use Oro\Bundle\ProductBundle\Form\Type\ProductSelectType;
use Oro\Bundle\ProductBundle\EventListener\ScopedProductSearchQueryRestrictionEventListener;

class ScopedProductSearchQueryRestrictionEventListenerTest extends ProductSearchQueryRestrictionEventListenerTest
{
    const SCOPE = 'test_scope';

    /**
     * @var ScopedProductSearchQueryRestrictionEventListener
     */
    protected $listener;

    /**
     * @var RequestStack|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $requestStack;

    /**
     * @return ScopedProductSearchQueryRestrictionEventListener
     */
    protected function createListener()
    {
        $this->requestStack = $this->getMock(RequestStack::class);

        $listener = new ScopedProductSearchQueryRestrictionEventListener(
            $this->configManager,
            $this->modifier,
            $this->frontendHelper,
            $this->frontendConfigPath
        );

        $listener->setScope(self::SCOPE);
        $listener->setRequestStack($this->requestStack);

        return $listener;
    }

    public function testOnQueryWithGoodScope()
    {
        $request = $this->getMock(Request::class);
        $request->expects($this->once())
            ->method('get')
            ->with(ProductSelectType::DATA_PARAMETERS)
            ->willReturn(['scope' => self::SCOPE]);

        $this->requestStack->expects($this->any())
            ->method('getCurrentRequest')
            ->willReturn($request);

        parent::testOnSearchQueryInFrontend();
    }

    public function testOnQueryWrongScope()
    {
        $this->modifier->expects($this->never())
            ->method($this->anything());

        $this->listener->setScope(self::SCOPE);
        $this->listener->setFrontendSystemConfigurationPath('path');

        $request = $this->getMock(Request::class);
        $request->expects($this->once())
            ->method('get')
            ->with(ProductSelectType::DATA_PARAMETERS)
            ->willReturn(['scope' => 'bad_scope']);

        $this->requestStack->expects($this->any())
            ->method('getCurrentRequest')
            ->willReturn($request);

        $this->listener->onSearchQuery($this->getEvent());
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Scope not configured for ProductSearchQueryRestrictionEventListener
     */
    public function testScopeEmpty()
    {
        $this->listener->setFrontendSystemConfigurationPath('path');
        $this->listener->setScope(null);

        $this->listener->onSearchQuery($this->getEvent());
    }
}
