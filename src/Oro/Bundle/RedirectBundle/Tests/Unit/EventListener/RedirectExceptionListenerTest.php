<?php

namespace Oro\Bundle\RedirectBundle\Tests\Unit\EventListener;

use Oro\Bundle\RedirectBundle\Entity\Redirect;
use Oro\Bundle\RedirectBundle\Entity\Repository\RedirectRepository;
use Oro\Bundle\RedirectBundle\EventListener\RedirectExceptionListener;
use Oro\Bundle\RedirectBundle\Routing\MatchedUrlDecisionMaker;
use Oro\Bundle\RedirectBundle\Routing\SluggableUrlGenerator;
use Oro\Bundle\ScopeBundle\Manager\ScopeManager;
use Oro\Bundle\ScopeBundle\Model\ScopeCriteria;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class RedirectExceptionListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var RedirectRepository|\PHPUnit_Framework_MockObject_MockObject
     */
    private $repository;

    /**
     * @var ScopeManager|\PHPUnit_Framework_MockObject_MockObject
     */
    private $scopeManager;

    /**
     * @var MatchedUrlDecisionMaker|\PHPUnit_Framework_MockObject_MockObject
     */
    private $matchedUrlDecisionMaker;

    /**
     * @var RedirectExceptionListener
     */
    private $listener;

    protected function setUp()
    {
        $this->repository = $this->createMock(RedirectRepository::class);
        $this->scopeManager = $this->createMock(ScopeManager::class);
        $this->matchedUrlDecisionMaker = $this->createMock(MatchedUrlDecisionMaker::class);

        $this->listener = new RedirectExceptionListener(
            $this->repository,
            $this->scopeManager,
            $this->matchedUrlDecisionMaker
        );
    }

    /**
     * @dataProvider skipDataProvider
     * @param bool $hasResponse
     * @param bool $isMaster
     * @param \Exception $exception
     */
    public function testOnKernelExceptionNoProcessed($hasResponse, $isMaster, \Exception  $exception)
    {
        $event = $this->getEvent(Request::create('/test'), $hasResponse, $isMaster, $exception);
        $event->expects($this->never())
            ->method('setResponse');
        $this->matchedUrlDecisionMaker->expects($this->any())
            ->method('matches')
            ->willReturn(true);

        $this->listener->onKernelException($event);
    }

    /**
     * @return array
     */
    public function skipDataProvider()
    {
        return [
            'non master' => [false, false, new NotFoundHttpException()],
            'has response' => [true, true, new NotFoundHttpException()],
            'unsupported exception' => [false, true, new \Exception()]
        ];
    }

    public function testOnKernelExceptionNotMatchedUrl()
    {
        $event = $this->getEvent(Request::create('/test'), false, true, new NotFoundHttpException());
        $event->expects($this->never())
            ->method('setResponse');
        $this->matchedUrlDecisionMaker->expects($this->any())
            ->method('matches')
            ->willReturn(false);

        $this->listener->onKernelException($event);
    }

    public function testOnKernelExceptionNoRedirect()
    {
        $request = Request::create('/test');
        $event = $this->getEvent($request, false, true, new NotFoundHttpException());
        $event->expects($this->never())
            ->method('setResponse');
        $this->matchedUrlDecisionMaker->expects($this->any())
            ->method('matches')
            ->with('/test')
            ->willReturn(true);

        $scopeCriteria = $this->getMockBuilder(ScopeCriteria::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->scopeManager->expects($this->once())
            ->method('getCriteria')
            ->with('web_content')
            ->willReturn($scopeCriteria);
        $this->repository->expects($this->once())
            ->method('findByUrl')
            ->with('/test', $scopeCriteria)
            ->willReturn(null);

        $this->listener->onKernelException($event);
    }

    public function testOnKernelExceptionRedirectByPrototype()
    {
        $url = '/context/' . SluggableUrlGenerator::CONTEXT_DELIMITER . '/test';

        $request = Request::create($url);
        $event = $this->getEvent($request, false, true, new NotFoundHttpException());
        $this->matchedUrlDecisionMaker->expects($this->any())
            ->method('matches')
            ->with($url)
            ->willReturn(true);

        $scopeCriteria = $this->createMock(ScopeCriteria::class);

        $this->scopeManager->expects($this->once())
            ->method('getCriteria')
            ->with('web_content')
            ->willReturn($scopeCriteria);

        $contextRedirect = new Redirect();
        $contextRedirect->setTo('/context-new');
        $contextRedirect->setType(301);

        $this->repository->expects($this->any())
            ->method('findByUrl')
            ->willReturnMap(
                [
                    [$url, $scopeCriteria, null],
                    ['/context', $scopeCriteria, $contextRedirect],
                ]
            );

        $prototypeRedirect = new Redirect();
        $prototypeRedirect->setToPrototype('test-new');
        $prototypeRedirect->setType(301);

        $this->repository->expects($this->any())
            ->method('findByPrototype')
            ->with('test', $scopeCriteria)
            ->willReturn($prototypeRedirect);

        $event->expects($this->once())
            ->method('setResponse')
            ->willReturnCallback(
                function (RedirectResponse $response) {
                    $this->assertEquals(301, $response->getStatusCode());
                    $this->assertEquals(
                        '/context-new/' . SluggableUrlGenerator::CONTEXT_DELIMITER . '/test-new',
                        $response->getTargetUrl()
                    );
                }
            );

        $this->listener->onKernelException($event);
    }

    public function testOnKernelException()
    {
        $request = Request::create('/test');
        $event = $this->getEvent($request, false, true, new NotFoundHttpException());
        $this->matchedUrlDecisionMaker->expects($this->any())
            ->method('matches')
            ->with('/test')
            ->willReturn(true);

        $scopeCriteria = $this->getMockBuilder(ScopeCriteria::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->scopeManager->expects($this->once())
            ->method('getCriteria')
            ->with('web_content')
            ->willReturn($scopeCriteria);

        $redirect = new Redirect();
        $redirect->setTo('/test-new');
        $redirect->setType(301);
        $this->repository->expects($this->once())
            ->method('findByUrl')
            ->with('/test', $scopeCriteria)
            ->willReturn($redirect);

        $event->expects($this->once())
            ->method('setResponse')
            ->willReturnCallback(
                function (RedirectResponse $response) {
                    $this->assertEquals(301, $response->getStatusCode());
                    $this->assertEquals(
                        '/test-new',
                        $response->getTargetUrl()
                    );
                }
            );

        $this->listener->onKernelException($event);
    }

    /**
     * @param Request $request
     * @param bool $hasResponse
     * @param bool $isMaster
     * @param \Exception $exception
     * @return GetResponseForExceptionEvent|\PHPUnit_Framework_MockObject_MockObject
     */
    private function getEvent(Request $request, $hasResponse, $isMaster, \Exception  $exception)
    {
        $event = $this->getMockBuilder(GetResponseForExceptionEvent::class)
            ->disableOriginalConstructor()
            ->getMock();
        $event->expects($this->any())
            ->method('getRequest')
            ->willReturn($request);
        $event->expects($this->any())
            ->method('hasResponse')
            ->willReturn($hasResponse);
        $event->expects($this->any())
            ->method('isMasterRequest')
            ->willReturn($isMaster);
        $event->expects($this->any())
            ->method('getException')
            ->willReturn($exception);

        return $event;
    }
}
