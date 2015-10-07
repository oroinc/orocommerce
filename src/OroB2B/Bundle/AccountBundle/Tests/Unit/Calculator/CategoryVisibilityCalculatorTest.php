<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Unit\Calculator;

use Symfony\Component\Yaml\Yaml;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Doctrine\Common\Persistence\ManagerRegistry;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;

use OroB2B\Bundle\AccountBundle\Calculator\CategoryVisibilityCalculator;
use OroB2B\Bundle\AccountBundle\Entity\Account;

class CategoryVisibilityCalculatorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var CategoryVisibilityCalculator
     */
    protected $calculator;

    /**
     * @var ManagerRegistry|\PHPUnit_Framework_MockObject_MockObject $managerRegistry
     */
    protected $managerRegistry;

    /**
     * @var ConfigManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $configManager;

    /**
     * @var ContainerInterface
     */
    protected $container;

    public function setUp()
    {
        $this->managerRegistry = $this->getMock('\Doctrine\Common\Persistence\ManagerRegistry');
        $this->configManager = $this->getMockBuilder('\Oro\Bundle\ConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->container = $this->getMock('\Symfony\Component\DependencyInjection\ContainerInterface');
        $this->container->expects($this->any())
            ->method('get')
            ->with('oro_config.manager')
            ->willReturn($this->configManager);

        $this->calculator = new CategoryVisibilityCalculator($this->managerRegistry);
        $this->calculator->setContainer($this->container);
    }

    /**
     * @dataProvider calculateVisibleDataProvider
     *
     * @param array $expected
     * @param string $configValue
     * @param array $visibilities
     */
    public function testCalculateVisible($expected, $configValue, $visibilities)
    {
        $account = new Account();

        $this->configManager->expects($this->any())
            ->method('get')
            ->with(
                CategoryVisibilityCalculator::CATEGORY_VISIBILITY_CONFIG_VALUE_KEY
            )
            ->willReturn($configValue);

        $repo = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->setMethods(['getVisibilityToAll'])
            ->getMock();
        $repo->expects($this->once())
            ->method('getVisibilityToAll')
            ->with($account)
            ->willReturn($visibilities);

        $em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $em->expects($this->once())
                ->method('getRepository')
                ->with('OroB2BAccountBundle:Visibility\CategoryVisibility')
                ->willReturn($repo);
        $this->managerRegistry->expects($this->once())
            ->method('getManagerForClass')
            ->with('OroB2BAccountBundle:Visibility\CategoryVisibility')
            ->willReturn($em);

        $actual = $this->calculator->getVisibility($account);
        $this->assertEquals($expected, $actual);
    }

    /**
     * @return array
     */
    public function calculateVisibleDataProvider()
    {
        $filePath = __DIR__.DIRECTORY_SEPARATOR.'data'.DIRECTORY_SEPARATOR.'visibilities.yml';

        return Yaml::parse($filePath);
    }
}
