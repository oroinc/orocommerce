<?php

namespace Oro\Bundle\RedirectBundle\Tests\Unit\Generator;

use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\RedirectBundle\Entity\Redirect;
use Oro\Bundle\RedirectBundle\Entity\Repository\RedirectRepository;
use Oro\Bundle\RedirectBundle\Entity\Slug;
use Oro\Bundle\RedirectBundle\Generator\RedirectGenerator;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Bridge\Doctrine\ManagerRegistry;

class RedirectGeneratorTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /**
     * @var ManagerRegistry|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $registry;

    /**
     * @var ConfigManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $configManager;

    /**
     * @var RedirectGenerator
     */
    protected $redirectGenerator;

    protected function setUp()
    {
        $this->registry = $this->getMockBuilder(ManagerRegistry::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->configManager = $this->getMockBuilder(ConfigManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->redirectGenerator = new RedirectGenerator($this->registry, $this->configManager);
    }

    public function testGenerateWithExistingRedirect()
    {
        $from = '/from';
        $to = '/to-url';
        $slug = new Slug();
        $slug->setUrl($to);

        $existingRedirect = $this->getEntity(Redirect::class, ['id' => 42]);
        $createdRedirect = new Redirect();
        $createdRedirect->setFrom($from)
            ->setTo($to)
            ->setSlug($slug)
            ->setType(Redirect::MOVED_PERMANENTLY);


        $repository = $this->createMock(RedirectRepository::class);
        $repository->expects($this->once())
            ->method('findBy')
            ->with(['slug' => $slug])
            ->willReturn([$existingRedirect]);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->any())
            ->method('getRepository')
            ->with(Redirect::class)
            ->willReturn($repository);

        $this->registry->expects($this->any())
            ->method('getManagerForClass')
            ->with(Redirect::class)
            ->willReturn($em);

        $em->expects($this->once())
            ->method('persist')
            ->with($createdRedirect);

        $em->expects($this->once())
            ->method('flush')
            ->with($createdRedirect);

        $this->redirectGenerator->generate($from, $slug);

        $this->assertEquals($slug->getUrl(), $existingRedirect->getTo());
        $this->assertEquals($slug->getUrl(), $createdRedirect->getTo());
    }

    public function testGenerateWithoutExistingRedirect()
    {
        $from = '/from';
        $to = '/to-url';
        $slug = new Slug();
        $slug->setUrl($to);

        $expectedRedirect = new Redirect();
        $expectedRedirect->setFrom($from)
            ->setTo($to)
            ->setSlug($slug)
            ->setType(Redirect::MOVED_PERMANENTLY);

        $repository = $this->createMock(RedirectRepository::class);
        $repository->expects($this->once())
            ->method('findBy')
            ->with(['slug' => $slug])
            ->willReturn(null);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())
            ->method('getRepository')
            ->with(Redirect::class)
            ->willReturn($repository);

        $em->expects($this->once())
            ->method('persist')
            ->with($expectedRedirect);

        $em->expects($this->once())
            ->method('flush')
            ->with($expectedRedirect);

        $this->registry->expects($this->any())
            ->method('getManagerForClass')
            ->with(Redirect::class)
            ->willReturn($em);

        $this->redirectGenerator->generate($from, $slug);
        $this->assertEquals($slug->getUrl(), $expectedRedirect->getTo());
    }
}
