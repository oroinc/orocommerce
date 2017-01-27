<?php

namespace Oro\Bundle\FrontendBundle\Tests\Unit\Migrations\Data\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\FrontendBundle\Migrations\Data\ORM\AbstractRolesData;
use Oro\Bundle\SecurityBundle\Acl\Persistence\AclManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Kernel;

class AbstractRolesDataTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Call Kernel::locateResource with $first=true, block all bundles data loading and should not happens.
     */
    public function testLoadRolesDataEnsureThatKernelsLocateResourceCalledProperly()
    {
        /** @var ObjectManager|\PHPUnit_Framework_MockObject_MockObject $manager */
        $manager = $this->createMock(ObjectManager::class);
        $aclManager = $this->createMock(AclManager::class);

        $yamlContent = "SOME_KEY:\n\r    label: Value\n\r";
        $kernel = $this->createMock(Kernel::class);
        $kernel->expects($this->exactly(3))
            ->method('locateResource')
            ->withConsecutive(
                ['@Oro\SomeBundle/Migrations/Data/ORM/data/', null, false],
                ['@Oro\AnotherBundle/Migrations/Data/ORM/data/', null, false],
                ['@Oro\AnotherOneBundle/Migrations/Data/ORM/data/', null, false]
            )
            ->willReturnOnConsecutiveCalls(
                [$yamlContent],
                [$yamlContent],
                [$yamlContent]
            );

        /** @var ContainerInterface|\PHPUnit_Framework_MockObject_MockObject $container */
        $container = $this->createMock(ContainerInterface::class);
        $container->expects($this->any())
            ->method('get')
            ->willReturnCallback(function ($name) use ($aclManager, $kernel) {
                if ('oro_security.acl.manager' === $name) {
                    return $aclManager;
                } elseif ('kernel' === $name) {
                    return $kernel;
                }
                return null;
            });
        $container->expects($this->any())
            ->method('getParameter')
            ->with('kernel.bundles')
            ->will($this->returnValue(
                ['Oro\SomeBundle' => [], 'Oro\AnotherBundle' => [], 'Oro\AnotherOneBundle' => []]
            ));

        /** @var AbstractRolesData|\PHPUnit_Framework_MockObject_MockObject $abstractRolesData */
        $abstractRolesData = $this->getMockForAbstractClass(AbstractRolesData::class);
        $abstractRolesData->setContainer($container);

        $abstractRolesData->load($manager);
    }
}
