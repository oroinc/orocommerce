<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Api\Processor;

use Oro\Bundle\ApiBundle\Tests\Unit\Processor\Get\GetProcessorTestCase;
use Oro\Bundle\ProductBundle\Api\Processor\RelatedItemSecurityCheck;
use Oro\Bundle\SecurityBundle\Tests\Unit\Authorization\FakeAuthorizationChecker;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class RelatedItemSecurityCheckTest extends GetProcessorTestCase
{
    /**
     * @dataProvider withoutProperCapabilitiesDataProvider
     * @param array $capabilities
     * @param array $isGrantedMapping
     */
    public function testAccessIsDeniedWhenUserDoesNotHaveProperCapability(array $capabilities, array $isGrantedMapping)
    {
        $processor = $this->getSecurityCheck($capabilities, [], $isGrantedMapping);

        $this->expectException(AccessDeniedException::class);

        $processor->process($this->context);
    }

    /**
     * @dataProvider withoutProperPermissionsDataProvider
     * @param array $permissions
     * @param array $isGrantedMapping
     */
    public function testAccessIsDeniedWhenUserDoesNotHaveProperPermissions(array $permissions, array $isGrantedMapping)
    {
        $processor = $this->getSecurityCheck([], $permissions, $isGrantedMapping);

        $this->expectException(AccessDeniedException::class);

        $processor->process($this->context);
    }

    public function testAccessGrantedAndSecurityCheckGroupSkippedWhenEmptyArrayPassed()
    {
        $processor = $this->getSecurityCheck([], [], []);

        $processor->process($this->context);
        self::assertEquals(['security_check'], $this->context->getSkippedGroups());
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
}
