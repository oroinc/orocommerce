<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Unit\Layout\DataProvider;

use Oro\Component\Layout\LayoutContext;

use OroB2B\Bundle\AccountBundle\Entity\AccountUserRole;
use OroB2B\Bundle\AccountBundle\Helper\AccountUserRolePrivilegesHelper;
use OroB2B\Bundle\AccountBundle\Layout\DataProvider\FrontendAccountUserRolePrivilegesDataProvider;

class FrontendAccountUserRolePrivilegesDataProviderTest extends \PHPUnit_Framework_TestCase
{
    /** @var AccountUserRolePrivilegesHelper|\PHPUnit_Framework_MockObject_MockObject */
    protected $privilegesHelper;

    /** @var FrontendAccountUserRolePrivilegesDataProvider */
    protected $dataProvider;

    protected function setUp()
    {
        $this->privilegesHelper = $this
            ->getMockBuilder('OroB2B\Bundle\AccountBundle\Helper\AccountUserRolePrivilegesHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->dataProvider = new FrontendAccountUserRolePrivilegesDataProvider($this->privilegesHelper);
    }

    protected function tearDown()
    {
        unset($this->privilegesHelper, $this->dataProvider);
    }

    public function testGetDataWithCorrectRole()
    {
        $role = new AccountUserRole();

        $context = new LayoutContext();
        $context->data()->set('entity', 'entity', $role);

        $this->privilegesHelper->expects($this->once())
            ->method('collect')
            ->with($role)
            ->willReturn(['data' => [], 'privilegesConfig' => [], 'accessLevelNames' => []]);

        $this->assertEquals(
            ['data' => [], 'privilegesConfig' => [], 'accessLevelNames' => []],
            $this->dataProvider->getData($context)
        );
    }

    /**
     * @dataProvider getDataWithIncorrectRoleProvider
     *
     * @param LayoutContext $context
     */
    public function testGetDataWithIncorrectRole(LayoutContext $context)
    {
        $this->privilegesHelper->expects($this->never())->method($this->anything());

        $this->assertEquals(null, $this->dataProvider->getData($context));
    }

    /**
     * @return array
     */
    public function getDataWithIncorrectRoleProvider()
    {
        $context = new LayoutContext();
        $context->data()->set('entity', 'entity', new \stdClass());

        return [
            [new LayoutContext()],
            [$context]
        ];
    }
}
