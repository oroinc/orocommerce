<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Unit\Layout\DataProvider;

use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\UserBundle\Model\PrivilegeCategory;
use Oro\Bundle\UserBundle\Provider\RolePrivilegeCapabilityProvider;
use Oro\Bundle\UserBundle\Provider\RolePrivilegeCategoryProvider;

use Oro\Component\Layout\ContextDataCollection;
use Oro\Component\Layout\ContextInterface;
use Oro\Component\Testing\Unit\EntityTrait;

use OroB2B\Bundle\AccountBundle\Entity\AccountUserRole;
use OroB2B\Bundle\AccountBundle\Layout\DataProvider\FrontendAccountUserRoleOptionsDataProvider;

class FrontendAccountUserRoleOptionsDataProviderTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /** @var RolePrivilegeCapabilityProvider|\PHPUnit_Framework_MockObject_MockObject */
    protected $capabilityProvider;

    /** @var RolePrivilegeCategoryProvider|\PHPUnit_Framework_MockObject_MockObject */
    protected $categoryProvider;

    /** @var TranslatorInterface|\PHPUnit_Framework_MockObject_MockObject*/
    protected $translator;

    /** @var FrontendAccountUserRoleOptionsDataProvider */
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

        $this->provider = new FrontendAccountUserRoleOptionsDataProvider(
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

    public function testGetData()
    {
        $this->capabilityProvider->expects($this->once())
            ->method('getCapabilities')
            ->with($this->role)
            ->willReturn(['capabilities_data']);

        $category1 = new PrivilegeCategory(35, 'cat1', 'tab1', 0);
        $category2 = new PrivilegeCategory(42, 'cat2', 'tab2', 0);

        $this->categoryProvider->expects($this->once())
            ->method('getTabbedCategories')
            ->willReturn([$category1, $category2]);
        $this->categoryProvider->expects($this->once())
            ->method('getTabList')
            ->willReturn(['tab_list_data']);

        $this->translator->expects($this->exactly(2))
            ->method('trans')
            ->willReturnCallback(
                function ($label) {
                    return 'translated_' . $label;
                }
            );

        $context = $this->getLayoutContext();

        $firstResult = $this->provider->getData($context);

        $this->assertInternalType('array', $firstResult);

        $this->assertArrayHasKey('tabsOptions', $firstResult);
        $this->assertArrayHasKey('data', $firstResult['tabsOptions']);
        $this->assertEquals(
            [
                ['id' => 35, 'label' => 'translated_cat1'],
                ['id' => 42, 'label' => 'translated_cat2']
            ],
            $firstResult['tabsOptions']['data']
        );

        $this->assertArrayHasKey('capabilitySetOptions', $firstResult);
        $this->assertArrayHasKey('data', $firstResult['capabilitySetOptions']);
        $this->assertEquals(['capabilities_data'], $firstResult['capabilitySetOptions']['data']);
        $this->assertArrayHasKey('tabIds', $firstResult['capabilitySetOptions']);
        $this->assertEquals(['tab_list_data'], $firstResult['capabilitySetOptions']['tabIds']);
        
        //expected result from cache
        $secondResult = $this->provider->getData($context);

        $this->assertEquals($secondResult, $firstResult);
    }

    /**
     * @return ContextInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getLayoutContext()
    {
        /** @var ContextDataCollection|\PHPUnit_Framework_MockObject_MockObject $data */
        $data = $this->getMockBuilder('Oro\Component\Layout\ContextDataCollection')
            ->disableOriginalConstructor()
            ->getMock();
        $data->expects($this->once())->method('get')->with('entity')->willReturn($this->role);

        /** @var ContextInterface|\PHPUnit_Framework_MockObject_MockObject $context */
        $context = $this->getMock('Oro\Component\Layout\ContextInterface');
        $context->expects($this->once())->method('data')->willReturn($data);

        return $context;
    }
}
