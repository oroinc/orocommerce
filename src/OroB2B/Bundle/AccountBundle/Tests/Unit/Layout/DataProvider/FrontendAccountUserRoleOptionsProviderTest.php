<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Unit\Layout\DataProvider;

use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\UserBundle\Model\PrivilegeCategory;
use Oro\Bundle\UserBundle\Provider\RolePrivilegeCapabilityProvider;
use Oro\Bundle\UserBundle\Provider\RolePrivilegeCategoryProvider;

use Oro\Component\Testing\Unit\EntityTrait;

use OroB2B\Bundle\AccountBundle\Entity\AccountUserRole;
use OroB2B\Bundle\AccountBundle\Layout\DataProvider\FrontendAccountUserRoleOptionsProvider;

class FrontendAccountUserRoleOptionsProviderTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /** @var RolePrivilegeCapabilityProvider|\PHPUnit_Framework_MockObject_MockObject */
    protected $capabilityProvider;

    /** @var RolePrivilegeCategoryProvider|\PHPUnit_Framework_MockObject_MockObject */
    protected $categoryProvider;

    /** @var TranslatorInterface|\PHPUnit_Framework_MockObject_MockObject*/
    protected $translator;

    /** @var FrontendAccountUserRoleOptionsProvider */
    protected $provider;

    /** @var AccountUserRole */
    protected $role;

    protected function setUp()
    {
        $this->capabilityProvider = $this
            ->getMockBuilder('Oro\Bundle\UserBundle\Provider\RolePrivilegeCapabilityProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->categoryProvider = $this->getMockBuilder('Oro\Bundle\UserBundle\Provider\RolePrivilegeCategoryProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->translator = $this->getMock('Symfony\Component\Translation\TranslatorInterface');
        $this->translator->expects($this->any())
            ->method('trans')
            ->willReturnCallback(
                function ($label) {
                    return 'translated_' . $label;
                }
            );

        $this->provider = new FrontendAccountUserRoleOptionsProvider(
            $this->capabilityProvider,
            $this->categoryProvider,
            $this->translator
        );

        $this->role = $this->getEntity('OroB2B\Bundle\AccountBundle\Entity\AccountUserRole');
    }

    protected function tearDown()
    {
        unset($this->provider, $this->capabilityProvider, $this->categoryProvider, $this->translator);
    }

    public function testGetTabsOptions()
    {
        $category1 = new PrivilegeCategory(35, 'cat1', 'tab1', 0);
        $category2 = new PrivilegeCategory(42, 'cat2', 'tab2', 0);

        $this->categoryProvider->expects($this->once())
            ->method('getTabbedCategories')
            ->willReturn([$category1, $category2]);

        $firstResult = $this->provider->getTabsOptions();

        $this->assertArrayHasKey('data', $firstResult);
        $this->assertEquals(
            [
                ['id' => 35, 'label' => 'translated_cat1'],
                ['id' => 42, 'label' => 'translated_cat2']
            ],
            $firstResult['data']
        );

        //expected result from cache
        $secondResult = $this->provider->getTabsOptions();
        $this->assertEquals($secondResult, $firstResult);
    }

    public function testGetCapabilitySetOptions()
    {
        $this->capabilityProvider->expects($this->once())
            ->method('getCapabilities')
            ->with($this->role)
            ->willReturn(['capabilities_data']);

        $this->categoryProvider->expects($this->once())
            ->method('getTabList')
            ->willReturn(['tab_list_data']);

        $firstResult = $this->provider->getCapabilitySetOptions($this->role);

        $this->assertArrayHasKey('data', $firstResult);
        $this->assertArrayHasKey('tabIds', $firstResult);

        $this->assertEquals(['capabilities_data'], $firstResult['data']);
        $this->assertEquals(['tab_list_data'], $firstResult['tabIds']);

        //expected result from cache
        $secondResult = $this->provider->getCapabilitySetOptions($this->role);
        $this->assertEquals($secondResult, $firstResult);
    }
}
