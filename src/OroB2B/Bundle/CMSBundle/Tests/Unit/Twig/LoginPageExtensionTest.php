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

    public function setUp()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|ManagerRegistry $managerRegistry */
        $managerRegistry = $this->getMockBuilder('Doctrine\Common\Persistence\ManagerRegistry')
            ->disableOriginalConstructor()
            ->getMock();

        /** @var \PHPUnit_Framework_MockObject_MockObject|ObjectRepository $objectRepository */
        $objectRepository = $this->getMock('Doctrine\Common\Persistence\ObjectRepository');

        $backgroundFile = new File();
        $logoFile = new File();

        $this->loginPage = new LoginPage();
        $this->loginPage->setTopContent('top');
        $this->loginPage->setBottomContent('bottom');
        $this->loginPage->setCss('css');

        $this->loginPage->setBackgroundImage($backgroundFile);
        $this->loginPage->setLogoImage($logoFile);

        $objectRepository->expects($this->any())
            ->method('findOneBy')
            ->with([])
            ->willReturn($this->loginPage);

        /** @var \PHPUnit_Framework_MockObject_MockObject|ObjectManager $objectManager */
        $objectManager = $this->getMock('Doctrine\Common\Persistence\ObjectManager');

        $objectManager->expects($this->exactly(1))
            ->method('getRepository')
            ->with('OroB2BCMSBundle:LoginPage')
            ->willReturn($objectRepository);

        $managerRegistry->expects($this->exactly(1))
            ->method('getManagerForClass')
            ->with('OroB2BCMSBundle:LoginPage')
            ->willReturn($objectManager);

        $this->extension = new LoginPageExtension($managerRegistry);
    }

    public function testGetName()
    {
        $this->assertEquals(LoginPageExtension::NAME, $this->extension->getName());
    }

    /**
     * Test related method
     */
    public function testGetFunctions()
    {
        $functionList = [
            ['orob2b_login_css', 'getCss'],
            ['orob2b_login_top_content', 'getTopContent'],
            ['orob2b_login_bottom_content', 'getBottomContent'],
            ['orob2b_login_logo_image', 'getLogoImage'],
            ['orob2b_login_background_image', 'getBackgroundImage'],
        ];

        $functions = $this->extension->getFunctions();
        $this->assertCount(count($functionList), $functions);


        for ($i = 0; $i < count($functionList); $i++) {
            /** @var \Twig_SimpleFunction $function */
            $function = $functions[$i];
            $this->assertInstanceOf('\Twig_SimpleFunction', $function);
            $this->assertEquals(key($functionList[$i][0]), $function->getName());
            $this->assertEquals([$this->extension, $functionList[$i][1]], $function->getCallable());
        }
    }

    public function testGetCss()
    {

    }
}
