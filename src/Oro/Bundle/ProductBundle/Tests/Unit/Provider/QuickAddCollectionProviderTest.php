<?php

namespace Oro\Bundle\ProductBundle\Tests\UnitProvider;

use Oro\Bundle\ProductBundle\Form\Handler\QuickAddHandler;
use Oro\Bundle\ProductBundle\Provider\QuickAddCollectionProvider;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class QuickAddCollectionProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var QuickAddHandler|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $quickAddHandler;

    /**
     * @var RequestStack|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $requestStack;

    /**
     * @var QuickAddCollectionProvider
     */
    protected $provider;

    /**
     * @var Request|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $request;

    public function setUp()
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
