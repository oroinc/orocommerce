<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Processor\Shared;

use Oro\Bundle\ProductBundle\Processor\Shared\RelatedItemSecurityCheck;
use Oro\Bundle\SecurityBundle\Tests\Unit\Authorization\FakeAuthorizationChecker;
use Oro\Component\ChainProcessor\ContextInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class RelatedItemSecurityCheckTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider withoutProperCapabilitiesDataProvider
     * @param array $capabilities
     * @param array $isGrantedMapping
     */
    public function testAccessIsDeniedWhenUserDoesNotHaveProperCapability(array $capabilities, array $isGrantedMapping)
    {
        $securityCheck = $this->getSecurityCheck($capabilities, [], $isGrantedMapping);

        $this->expectException(AccessDeniedException::class);

        $securityCheck->process($this->getContext());
    }

    /**
     * @dataProvider withoutProperPermissionsDataProvider
     * @param array $permissions
     * @param array $isGrantedMapping
     */
    public function testAccessIsDeniedWhenUserDoesNotHaveProperPermissions(array $permissions, array $isGrantedMapping)
    {
        $securityCheck = $this->getSecurityCheck([], $permissions, $isGrantedMapping);

        $this->expectException(AccessDeniedException::class);

        $securityCheck->process($this->getContext());
    }

    public function testAccessGrantedAndSecurityCheckGroupSkippedWhenEmptyArrayPassed()
    {
        $securityCheck = $this->getSecurityCheck([], [], []);

        $context = $this->getContext();
        $context->expects($this->once())
            ->method('skipGroup')
            ->with('security_check');

        $securityCheck->process($context);
    }

    /**
     * @return array
     */
    public function withoutProperCapabilitiesDataProvider()
    {
        return [
            [['capability_1', 'capability_2'], ['capability_1' => true, 'capability_2' => false]],
            [['capability_1', 'capability_2'], ['capability_1' => false, 'capability_2' => true]],
            [['capability_1', 'capability_2'], ['capability_1' => false, 'capability_2' => false]],
            [['capability_1', 'capability_2'], ['capability_1' => false, 'capability_2' => false]],
        ];
    }

    /**
     * @return array
     */
    public function withoutProperPermissionsDataProvider()
    {
        return [
            [['VIEW', 'CREATE', 'DELETE'], ['VIEW' => false, 'CREATE' => true, 'DELETE' => true]],
            [['VIEW', 'CREATE', 'DELETE'], ['VIEW' => true, 'CREATE' => false, 'DELETE' => true]],
            [['VIEW', 'CREATE', 'DELETE'], ['VIEW' => true, 'CREATE' => true, 'DELETE' => false]],
            [['VIEW', 'CREATE', 'DELETE'], ['VIEW' => false, 'CREATE' => false, 'DELETE' => false]],
        ];
    }

    /**
     * @param array $capabilities
     * @param array $permissions
     * @param array $isGrantedMapping
     * @return RelatedItemSecurityCheck
     */
    private function getSecurityCheck(array $capabilities, array $permissions, array $isGrantedMapping)
    {
        $authChecker = new FakeAuthorizationChecker();
        $authChecker->isGrantedMapping = $isGrantedMapping;

        return new RelatedItemSecurityCheck($authChecker, $permissions, $capabilities);
    }

    /**
     * @return ContextInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private function getContext()
    {
        return $this->createMock(ContextInterface::class);
    }
}
