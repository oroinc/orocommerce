<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\EventListener;

use Oro\Bundle\ProductBundle\EventListener\ProductSearchQueryRestrictionEventListener;
use Oro\Bundle\ProductBundle\EventListener\ScopedProductSearchQueryRestrictionEventListener;
use Oro\Bundle\ProductBundle\Form\Type\ProductSelectType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

// @codingStandardsIgnoreStart
// CS-fixer tries to join the class name and extends, and the new line has more than 250 characters
class ScopedProductSearchQueryRestrictionEventListenerTest extends
    AbstractProductSearchQueryRestrictionEventListenerTest
    // @codingStandardsIgnoreEnd
{
    private const SCOPE = 'test_scope';

    /** @var ScopedProductSearchQueryRestrictionEventListener */
    protected $listener;

    /** @var RequestStack|\PHPUnit\Framework\MockObject\MockObject */
    private $requestStack;

    /**
     * {@inheritdoc}
     */
    protected function createListener(): ProductSearchQueryRestrictionEventListener
    {
        $this->requestStack = $this->createMock(RequestStack::class);

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
        $request = $this->createMock(Request::class);
        $request->expects($this->once())
            ->method('get')
            ->with(ProductSelectType::DATA_PARAMETERS)
            ->willReturn(['scope' => self::SCOPE]);

        $this->requestStack->expects($this->any())
            ->method('getCurrentRequest')
            ->willReturn($request);

        $this->configureDependenciesForFrontend();

        $this->listener->onSearchQuery($this->getEvent());
    }

    public function testOnQueryWrongScope()
    {
        $this->modifier->expects($this->never())
            ->method($this->anything());

        $this->listener->setScope(self::SCOPE);
        $this->listener->setFrontendSystemConfigurationPath('path');

        $request = $this->createMock(Request::class);
        $request->expects($this->once())
            ->method('get')
            ->with(ProductSelectType::DATA_PARAMETERS)
            ->willReturn(['scope' => 'bad_scope']);

        $this->requestStack->expects($this->any())
            ->method('getCurrentRequest')
            ->willReturn($request);

        $this->listener->onSearchQuery($this->getEvent());
    }

    public function testScopeEmpty()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Scope not configured for ProductSearchQueryRestrictionEventListener');

        $this->listener->setFrontendSystemConfigurationPath('path');
        $this->listener->setScope(null);

        $this->listener->onSearchQuery($this->getEvent());
    }
}
