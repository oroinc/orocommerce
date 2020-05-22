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

class RedirectGeneratorTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /**
     * @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $registry;

    /**
     * @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $configManager;

    /**
     * @var RedirectGenerator
     */
    protected $redirectGenerator;

    protected function setUp(): void
    {
        $this->registry = $this->createMock(ManagerRegistry::class);

        $this->redirectGenerator = new RedirectGenerator($this->registry);
    }

    public function testUpdateRedirectsSameUrl()
    {
        $url = '/test';
        $slug = new Slug();
        $slug->setUrl($url);

        $this->registry->expects($this->never())
            ->method($this->anything());

        $this->redirectGenerator->updateRedirects($url, $slug);
    }

    public function testUpdateRedirects()
    {
        $fromUrl = '/old';
        $url = '/test';
        $slug = new Slug();
        $slug->setUrl($url);

        $repository = $this->getMockBuilder(RedirectRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $em = $this->getEntityManagerMock();
        $em->expects($this->once())
            ->method('getRepository')
            ->with(Redirect::class)
            ->willReturn($repository);

        $repository->expects($this->once())
            ->method('updateRedirectsBySlug')
            ->with($slug);
        $repository->expects($this->once())
            ->method('deleteCyclicRedirects')
            ->with($slug);

        $this->redirectGenerator->updateRedirects($fromUrl, $slug);
    }

    public function testGenerateRedirectsCyclic()
    {
        $from = new Slug();
        $from->setUrl('/test');
        $from->setSlugPrototype('test');

        $slug = new Slug();
        $slug->setUrl($from->getUrl());
        $slug->setSlugPrototype($from->getSlugPrototype());

        $this->registry->expects($this->never())
            ->method($this->anything());

        $this->redirectGenerator->generateForSlug($from, $slug);
    }

    public function testGenerate()
    {
        $from = new Slug();
        $from->setUrl('/from');
        $from->setSlugPrototype('from');

        $slug = new Slug();
        $slug->setUrl('/to-url');
        $slug->setSlugPrototype('to-url');

        $expectedRedirect = new Redirect();
        $expectedRedirect->setFrom($from->getUrl())
            ->setFromPrototype($from->getSlugPrototype())
            ->setTo($slug->getUrl())
            ->setToPrototype($slug->getSlugPrototype())
            ->setSlug($slug)
            ->setType(Redirect::MOVED_PERMANENTLY);

        $em = $this->getEntityManagerMock();
        $em->expects($this->once())
            ->method('persist')
            ->with($expectedRedirect);

        $this->redirectGenerator->generateForSlug($from, $slug);
        $this->assertEquals($slug->getUrl(), $expectedRedirect->getTo());
    }

    /**
     * @return EntityManagerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private function getEntityManagerMock()
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $this->registry->expects($this->any())
            ->method('getManagerForClass')
            ->with(Redirect::class)
            ->willReturn($em);

        return $em;
    }
}
