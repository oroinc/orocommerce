<?php

namespace Oro\Bundle\InventoryBundle\Tests\Unit\EventListener;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\UIBundle\Event\BeforeListRenderEvent;
use Oro\Bundle\UIBundle\View\ScrollData;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

abstract class AbstractFallbackFieldsFormViewTest extends \PHPUnit\Framework\TestCase
{
    /** @var TranslatorInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $translator;

    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    protected $doctrineHelper;

    /** @var EntityManager|\PHPUnit\Framework\MockObject\MockObject */
    protected $em;

    /** @var Environment|\PHPUnit\Framework\MockObject\MockObject */
    protected $env;

    /** @var RequestStack */
    protected $requestStack;

    /** @var Request|\PHPUnit\Framework\MockObject\MockObject */
    protected $request;

    /** @var BeforeListRenderEvent */
    protected $event;

    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    protected $doctrine;

    abstract protected function callTestMethod(): void;

    abstract protected function getEntity(): object;

    abstract protected function getExpectedScrollData(): array;

    protected function setUp(): void
    {
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->translator->expects($this->any())
            ->method('trans')
            ->willReturnCallback(function ($id) {
                return $id . '.trans';
            });

        $this->env = $this->createMock(Environment::class);
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->em = $this->createMock(EntityManager::class);

        $this->request = $this->createMock(Request::class);
        $this->requestStack = new RequestStack();
        $this->requestStack->push($this->request);

        $this->doctrine = $this->createMock(ManagerRegistry::class);

        $this->event = new BeforeListRenderEvent(
            $this->env,
            new ScrollData(),
            new \stdClass()
        );
    }

    public function testOnCategoryEditIgnoredIfNoId()
    {
        $this->doctrine->expects($this->never())
            ->method('getManagerForClass');

        $this->callTestMethod();
    }

    public function testOnCategoryEditIgnoredIfNoFound()
    {
        $entity = $this->getEntity();
        $this->em->expects($this->once())
            ->method('getReference');

        $this->doctrine->expects($this->once())
            ->method('getManagerForClass')
            ->with(ClassUtils::getClass($entity))
            ->willReturn($this->em);

        $this->request->expects($this->once())
            ->method('get')
            ->willReturn('1');

        $this->callTestMethod();
    }

    public function testEditRendersAndAddsSubBlock()
    {
        $entity = $this->getEntity();
        $this->request->expects($this->once())
            ->method('get')
            ->willReturn('1');

        $this->em->expects($this->once())
            ->method('getReference')
            ->willReturn($entity);

        $this->doctrine->expects($this->once())
            ->method('getManagerForClass')
            ->with(ClassUtils::getClass($entity))
            ->willReturn($this->em);

        $this->event->getScrollData()->setData($this->getExpectedScrollData());

        $this->env->expects($this->once())
            ->method('render');

        $this->callTestMethod();
    }
}
