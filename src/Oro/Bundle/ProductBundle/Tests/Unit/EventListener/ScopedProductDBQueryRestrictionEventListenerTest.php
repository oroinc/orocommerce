<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\EventListener;

use Oro\Bundle\ProductBundle\EventListener\ScopedProductDBQueryRestrictionEventListener;
use Symfony\Component\HttpFoundation\ParameterBag;

class ScopedProductDBQueryRestrictionEventListenerTest extends ProductDBQueryRestrictionEventListenerTest
{
    private const SCOPE = 'test_scope';

    /** @var ScopedProductDBQueryRestrictionEventListener */
    protected $listener;

    /**
     * @return ScopedProductDBQueryRestrictionEventListener
     */
    protected function createListener()
    {
        $listener = new ScopedProductDBQueryRestrictionEventListener(
            $this->configManager,
            $this->modifier,
            $this->frontendHelper
        );
        $listener->setScope(self::SCOPE);

        return $listener;
    }

    /**
     * @dataProvider onQueryDataProvider
     */
    public function testOnQuery(bool $isFrontend, ?string $frontendPath, ?string $backendPath)
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

    public function testScopeEmpty()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Scope not configured for ProductDBQueryRestrictionEventListener');

        $this->listener->setBackendSystemConfigurationPath('path');
        $this->listener->setScope(null);

        $this->listener->onDBQuery($this->event);
    }
}
