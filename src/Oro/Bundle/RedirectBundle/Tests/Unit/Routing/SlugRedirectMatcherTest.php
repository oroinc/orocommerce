<?php

namespace Oro\Bundle\RedirectBundle\Tests\Unit\Routing;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\RedirectBundle\Entity\Redirect;
use Oro\Bundle\RedirectBundle\Entity\Repository\RedirectRepository;
use Oro\Bundle\RedirectBundle\Routing\SluggableUrlGenerator;
use Oro\Bundle\RedirectBundle\Routing\SlugRedirectMatcher;
use Oro\Bundle\ScopeBundle\Manager\ScopeManager;
use Oro\Bundle\ScopeBundle\Model\ScopeCriteria;

class SlugRedirectMatcherTest extends \PHPUnit\Framework\TestCase
{
    /** @var RedirectRepository|\PHPUnit\Framework\MockObject\MockObject */
    private $repository;

    /** @var ScopeManager|\PHPUnit\Framework\MockObject\MockObject */
    private $scopeManager;

    /** @var SlugRedirectMatcher */
    private $redirectMatcher;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(RedirectRepository::class);
        $this->scopeManager = $this->createMock(ScopeManager::class);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->any())
            ->method('getRepository')
            ->with(Redirect::class)
            ->willReturn($this->repository);

        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->expects($this->any())
            ->method('getManagerForClass')
            ->with(Redirect::class)
            ->willReturn($em);

        $this->redirectMatcher = new SlugRedirectMatcher($doctrine, $this->scopeManager);
    }

    public function testMatchWhenRedirectNotFound()
    {
        $url = '/test';

        $scopeCriteria = $this->createMock(ScopeCriteria::class);
        $this->scopeManager->expects($this->once())
            ->method('getCriteria')
            ->with('web_content')
            ->willReturn($scopeCriteria);
        $this->repository->expects($this->once())
            ->method('findByUrl')
            ->with($url, $scopeCriteria)
            ->willReturn(null);

        $this->assertNull($this->redirectMatcher->match($url));
    }

    public function testMatchWhenRedirectFound()
    {
        $url = '/test';

        $scopeCriteria = $this->createMock(ScopeCriteria::class);
        $this->scopeManager->expects($this->once())
            ->method('getCriteria')
            ->with('web_content')
            ->willReturn($scopeCriteria);

        $redirect = new Redirect();
        $redirect->setTo('/test-new');
        $redirect->setType(301);
        $this->repository->expects($this->once())
            ->method('findByUrl')
            ->with($url, $scopeCriteria)
            ->willReturn($redirect);

        $this->assertEquals(
            [
                'pathInfo'   => '/test-new',
                'statusCode' => 301
            ],
            $this->redirectMatcher->match($url)
        );
    }

    public function testMatchForRootUrl()
    {
        $url = '/';

        $scopeCriteria = $this->createMock(ScopeCriteria::class);
        $this->scopeManager->expects($this->once())
            ->method('getCriteria')
            ->with('web_content')
            ->willReturn($scopeCriteria);

        $redirect = new Redirect();
        $redirect->setTo('/root-new');
        $redirect->setType(301);
        $this->repository->expects($this->once())
            ->method('findByUrl')
            ->with($url, $scopeCriteria)
            ->willReturn($redirect);

        $this->assertEquals(
            [
                'pathInfo'   => '/root-new',
                'statusCode' => 301
            ],
            $this->redirectMatcher->match($url)
        );
    }

    public function testMatchWhenUrlEndsWithSlash()
    {
        $url = '/test/';

        $scopeCriteria = $this->createMock(ScopeCriteria::class);
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

        $this->assertEquals(
            [
                'pathInfo'   => '/test-new',
                'statusCode' => 301
            ],
            $this->redirectMatcher->match($url)
        );
    }

    public function testMatchForRedirectByPrototype()
    {
        $url = '/context/' . SluggableUrlGenerator::CONTEXT_DELIMITER . '/test';

        $scopeCriteria = $this->createMock(ScopeCriteria::class);
        $this->scopeManager->expects($this->once())
            ->method('getCriteria')
            ->with('web_content')
            ->willReturn($scopeCriteria);

        $contextRedirect = new Redirect();
        $contextRedirect->setTo('/context-new');
        $contextRedirect->setType(301);
        $this->repository->expects($this->once())
            ->method('findByUrl')
            ->with('/context', $scopeCriteria)
            ->willReturn($contextRedirect);

        $prototypeRedirect = new Redirect();
        $prototypeRedirect->setToPrototype('test-new');
        $prototypeRedirect->setType(301);
        $this->repository->expects($this->once())
            ->method('findByPrototype')
            ->with('test', $scopeCriteria)
            ->willReturn($prototypeRedirect);

        $this->assertEquals(
            [
                'pathInfo'   => '/context-new/' . SluggableUrlGenerator::CONTEXT_DELIMITER . '/test-new',
                'statusCode' => 301
            ],
            $this->redirectMatcher->match($url)
        );
    }

    public function testMatchForRedirectByPrototypeAndContextRedirectNotFound()
    {
        $url = '/context/' . SluggableUrlGenerator::CONTEXT_DELIMITER . '/test';

        $scopeCriteria = $this->createMock(ScopeCriteria::class);
        $this->scopeManager->expects($this->once())
            ->method('getCriteria')
            ->with('web_content')
            ->willReturn($scopeCriteria);

        $this->repository->expects($this->once())
            ->method('findByUrl')
            ->with('/context', $scopeCriteria)
            ->willReturn(null);

        $prototypeRedirect = new Redirect();
        $prototypeRedirect->setToPrototype('test-new');
        $prototypeRedirect->setType(301);
        $this->repository->expects($this->once())
            ->method('findByPrototype')
            ->with('test', $scopeCriteria)
            ->willReturn($prototypeRedirect);

        $this->assertEquals(
            [
                'pathInfo'   => '/context/' . SluggableUrlGenerator::CONTEXT_DELIMITER . '/test-new',
                'statusCode' => 301
            ],
            $this->redirectMatcher->match($url)
        );
    }

    public function testMatchForRedirectByPrototypeAndPrototypeRedirectNotFound()
    {
        $url = '/context/' . SluggableUrlGenerator::CONTEXT_DELIMITER . '/test';

        $scopeCriteria = $this->createMock(ScopeCriteria::class);
        $this->scopeManager->expects($this->once())
            ->method('getCriteria')
            ->with('web_content')
            ->willReturn($scopeCriteria);

        $contextRedirect = new Redirect();
        $contextRedirect->setTo('/context-new');
        $contextRedirect->setType(301);
        $this->repository->expects($this->once())
            ->method('findByUrl')
            ->with('/context', $scopeCriteria)
            ->willReturn($contextRedirect);

        $this->repository->expects($this->once())
            ->method('findByPrototype')
            ->with('test', $scopeCriteria)
            ->willReturn(null);

        $this->assertEquals(
            [
                'pathInfo'   => '/context-new/' . SluggableUrlGenerator::CONTEXT_DELIMITER . '/test',
                'statusCode' => 301
            ],
            $this->redirectMatcher->match($url)
        );
    }

    public function testMatchForRedirectByPrototypeAndBothContextAndPrototypeRedirectsNotFound()
    {
        $url = '/context/' . SluggableUrlGenerator::CONTEXT_DELIMITER . '/test';

        $scopeCriteria = $this->createMock(ScopeCriteria::class);
        $this->scopeManager->expects($this->once())
            ->method('getCriteria')
            ->with('web_content')
            ->willReturn($scopeCriteria);

        $redirect = new Redirect();
        $redirect->setTo('/test-new');
        $redirect->setType(301);
        $this->repository->expects($this->exactly(2))
            ->method('findByUrl')
            ->willReturnMap([
                ['/context', $scopeCriteria, null],
                [$url, $scopeCriteria, $redirect]
            ]);
        $this->repository->expects($this->once())
            ->method('findByPrototype')
            ->with('test', $scopeCriteria)
            ->willReturn(null);

        $this->assertEquals(
            [
                'pathInfo'   => '/test-new',
                'statusCode' => 301
            ],
            $this->redirectMatcher->match($url)
        );
    }
}
