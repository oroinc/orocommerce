<?php

namespace Oro\Bundle\FrontendNavigationBundle\Tests\Unit\Menu;

use Doctrine\ORM\EntityManager;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\FrontendNavigationBundle\Provider\MenuUpdateProvider;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Bundle\WebsiteBundle\Manager\WebsiteManager;

class MenuUpdateProviderTest extends \PHPUnit_Framework_TestCase
{
    const MENU = 'user_menu';

    /**
     * @var SecurityFacade|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $securityFacade;

    /**
     * @var WebsiteManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $websiteManager;

    /**
     * @var DoctrineHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $doctrineHelper;

    /**
     * @var LocalizationHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $localizationHelper;

    /**
     * @var EntityManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $manager;

    /**
     * @var MenuUpdateProvider
     */
    protected $provider;

    public function setUp()
    {
        $this->securityFacade = $this->getMockBuilder('Oro\Bundle\SecurityBundle\SecurityFacade')
            ->disableOriginalConstructor()
            ->getMock();

        $this->websiteManager = $this->getMockBuilder('Oro\Bundle\WebsiteBundle\Manager\WebsiteManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->doctrineHelper = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->localizationHelper = $this->getMockBuilder('Oro\Bundle\LocaleBundle\Helper\LocalizationHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->provider = new MenuUpdateProvider(
            $this->securityFacade,
            $this->doctrineHelper,
            $this->websiteManager,
            $this->localizationHelper
        );
    }

    public function testGetUpdates()
    {
        $organization = $this->getMock('Oro\Bundle\OrganizationBundle\Entity\Organization');
        $account = $this->getMock('Oro\Bundle\AccountBundle\Entity\Account');
        $accountUser = $this->getMock('Oro\Bundle\AccountBundle\Entity\AccountUser');
        $website = $this->getMock('Oro\Bundle\WebsiteBundle\Entity\Website');

        $accountUser->expects($this->once())
            ->method('getAccount')
            ->willReturn($account);

        $this->securityFacade->expects($this->once())
            ->method('getOrganization')
            ->willReturn($organization);

        $this->securityFacade->expects($this->once())
            ->method('getLoggedUser')
            ->willReturn($accountUser);

        $this->websiteManager->expects($this->once())
            ->method('getCurrentWebsite')
            ->willReturn($website);

        $menuUpdateRepository = $this
            ->getMockBuilder('Oro\Bundle\FrontendNavigationBundle\Entity\Repository\MenuUpdateRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityRepository')
            ->willReturn($menuUpdateRepository);

        $update = $this->getMock('Oro\Bundle\FrontendNavigationBundle\Entity\MenuUpdate');
        $update->expects($this->once())
            ->method('setTitle');
        $update->expects($this->once())
            ->method('getTitles')
            ->willReturn($this->getMock('Doctrine\Common\Collections\Collection'));

        $this->localizationHelper->expects($this->once())
            ->method('getLocalizedValue')
            ->willReturn('localized title');

        $menuUpdateRepository->expects($this->once())
            ->method('getUpdates')
            ->with(self::MENU, $organization, $account, $accountUser, $website)
            ->willReturn([$update]);

        $this->assertEquals([$update], $this->provider->getUpdates('user_menu'));
    }
}
