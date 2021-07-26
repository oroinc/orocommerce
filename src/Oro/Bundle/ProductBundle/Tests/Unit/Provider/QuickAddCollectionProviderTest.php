<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Provider;

use Oro\Bundle\ProductBundle\Form\Handler\QuickAddHandler;
use Oro\Bundle\ProductBundle\Provider\QuickAddCollectionProvider;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class QuickAddCollectionProviderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var QuickAddHandler|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $quickAddHandler;

    /**
     * @var RequestStack|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $requestStack;

    /**
     * @var QuickAddCollectionProvider
     */
    protected $provider;

    /**
     * @var Request|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $request;

    protected function setUp(): void
    {
        $this->quickAddHandler = $this->getMockBuilder(QuickAddHandler::class)->disableOriginalConstructor()->getMock();
        $this->requestStack = $this->getMockBuilder(RequestStack::class)->disableOriginalConstructor()->getMock();
        $this->request = $this->getMockBuilder(Request::class)->disableOriginalConstructor()->getMock();
        $this->requestStack->expects($this->any())->method('getCurrentRequest')->willReturn($this->request);
        $this->provider = new QuickAddCollectionProvider($this->quickAddHandler, $this->requestStack);
    }

    public function testProcessCopyPasteGetsCached()
    {
        $this->quickAddHandler->expects($this->once())
            ->method('processCopyPaste');
        $this->provider->processCopyPaste();
        $this->provider->processCopyPaste();
    }

    public function testProcessImportGetsCached()
    {
        $this->quickAddHandler->expects($this->once())
            ->method('processImport');
        $this->provider->processImport();
        $this->provider->processImport();
    }
}
