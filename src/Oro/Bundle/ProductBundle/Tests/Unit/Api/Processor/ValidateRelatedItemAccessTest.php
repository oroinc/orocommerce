<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Api\Processor;

use Oro\Bundle\ApiBundle\Request\ApiActionGroup;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\Get\GetProcessorTestCase;
use Oro\Bundle\ProductBundle\Api\Processor\ValidateRelatedItemAccess;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class ValidateRelatedItemAccessTest extends GetProcessorTestCase
{
    private function getProcessor(array $permissions, array $isGrantedMapping): ValidateRelatedItemAccess
    {
        $authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $authorizationChecker->expects(self::any())
            ->method('isGranted')
            ->willReturnCallback(function (string $attribute) use ($isGrantedMapping) {
                return $isGrantedMapping[$attribute] ?? true;
            });

        return new ValidateRelatedItemAccess($authorizationChecker, $permissions);
    }

    public function testAccessIsDeniedWhenUserDoesNotHaveCapabilityToEditRelatedProducts()
    {
        $this->expectException(AccessDeniedException::class);

        $processor = $this->getProcessor([], ['oro_related_products_edit' => false]);
        $processor->process($this->context);

        self::assertEquals([], $this->context->getSkippedGroups());
    }

    public function testAccessIsGrantedAndSecurityCheckGroupSkippedWhenUserHasCapabilityToEditRelatedProducts()
    {
        $processor = $this->getProcessor([], ['oro_related_products_edit' => true]);
        $processor->process($this->context);

        self::assertEquals([ApiActionGroup::SECURITY_CHECK], $this->context->getSkippedGroups());
    }

    /**
     * @dataProvider withoutProperPermissionsDataProvider
     */
    public function testAccessIsDeniedWhenUserDoesNotHaveProperPermissions(array $permissions, array $isGrantedMapping)
    {
        $this->expectException(AccessDeniedException::class);

        $isGrantedMapping['oro_related_products_edit'] = true;

        $processor = $this->getProcessor($permissions, $isGrantedMapping);
        $processor->process($this->context);

        self::assertEquals([], $this->context->getSkippedGroups());
    }

    public function testAccessGrantedAndSecurityCheckGroupSkippedWhenAllPermissionsAreGranted()
    {
        $isGrantedMapping = [
            'oro_related_products_edit' => true,
            'EDIT'                      => true,
            'VIEW'                      => true
        ];

        $processor = $this->getProcessor(['EDIT', 'VIEW'], $isGrantedMapping);
        $processor->process($this->context);

        self::assertEquals([ApiActionGroup::SECURITY_CHECK], $this->context->getSkippedGroups());
    }

    public function withoutProperPermissionsDataProvider(): array
    {
        return [
            [['VIEW', 'CREATE', 'DELETE'], ['VIEW' => false, 'CREATE' => true, 'DELETE' => true]],
            [['VIEW', 'CREATE', 'DELETE'], ['VIEW' => true, 'CREATE' => false, 'DELETE' => true]],
            [['VIEW', 'CREATE', 'DELETE'], ['VIEW' => true, 'CREATE' => true, 'DELETE' => false]],
            [['VIEW', 'CREATE', 'DELETE'], ['VIEW' => false, 'CREATE' => false, 'DELETE' => false]],
        ];
    }
}
