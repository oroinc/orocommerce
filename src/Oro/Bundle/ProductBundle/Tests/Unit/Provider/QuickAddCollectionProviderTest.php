<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Provider;

use Oro\Bundle\ProductBundle\Form\Handler\QuickAddHandler;
use Oro\Bundle\ProductBundle\Provider\QuickAddCollectionProvider;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class QuickAddCollectionProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var QuickAddHandler|\PHPUnit\Framework\MockObject\MockObject */
    private $quickAddHandler;

    /** @var RequestStack|\PHPUnit\Framework\MockObject\MockObject */
    private $requestStack;

    /** @var QuickAddCollectionProvider */
    private $provider;

    /** @var Request|\PHPUnit\Framework\MockObject\MockObject */
    private $request;

    protected function setUp(): void
    {
        $this->quickAddHandler = $this->createMock(QuickAddHandler::class);
        $this->requestStack = $this->createMock(RequestStack::class);
        $this->request = $this->createMock(Request::class);
        $this->requestStack->expects($this->any())
            ->method('getCurrentRequest')
            ->willReturn($this->request);

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
