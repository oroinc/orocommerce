<?php

namespace OroB2B\Bundle\CMSBundle\Tests\Unit\Twig;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Persistence\ObjectRepository;

use Oro\Bundle\AttachmentBundle\Entity\File;

use OroB2B\Bundle\CMSBundle\Entity\LoginPage;
use OroB2B\Bundle\CMSBundle\Twig\LoginPageExtension;

class LoginPageExtensionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var LoginPageExtension
     */
    protected $extension;

    /**
     * @var LoginPage
     */
    protected $loginPage;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ManagerRegistry
     */
    protected $managerRegistry;

    public function setUp()
    {
        $this->managerRegistry = $this->getMockBuilder('Doctrine\Common\Persistence\ManagerRegistry')
            ->disableOriginalConstructor()
            ->getMock();

        $backgroundFile = new File();
        $logoFile = new File();

        $this->loginPage = new LoginPage();
        $this->loginPage->setTopContent('top');
        $this->loginPage->setBottomContent('bottom');
        $this->loginPage->setCss('css');

        $this->extension = new LoginPageExtension($this->managerRegistry);
    }

    public function testGetName()
    {
        $this->assertEquals(LoginPageExtension::NAME, $this->extension->getName());
    }

    public function testGetFunctions()
    {
        $functionList = [
            ['orob2b_login_page', 'getDefaultLoginPage']
        ];

        $functions = $this->extension->getFunctions();
        $this->assertCount(count($functionList), $functions);

        for ($i = 0; $i < count($functionList); $i++) {
            /** @var \Twig_SimpleFunction $function */
            $function = $functions[$i];
            $this->assertInstanceOf('\Twig_SimpleFunction', $function);
            $this->assertEquals($functionList[$i][0], $function->getName());
            $this->assertEquals([$this->extension, $functionList[$i][1]], $function->getCallable());
        }
    }

    public function testGetDefaultLoginPage()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|ObjectRepository $objectRepository */
        $objectRepository = $this->getMock('Doctrine\Common\Persistence\ObjectRepository');

        $objectRepository->expects($this->once())
            ->method('findOneBy')
            ->with([])
            ->willReturn($this->loginPage);

        /** @var \PHPUnit_Framework_MockObject_MockObject|ObjectManager $objectManager */
        $objectManager = $this->getMock('Doctrine\Common\Persistence\ObjectManager');

        $objectManager->expects($this->once())
            ->method('getRepository')
            ->with('OroB2BCMSBundle:LoginPage')
            ->willReturn($objectRepository);

        $this->managerRegistry->expects($this->once())
            ->method('getManagerForClass')
            ->with('OroB2BCMSBundle:LoginPage')
            ->willReturn($objectManager);

        $this->extension->getDefaultLoginPage();
    }
}
