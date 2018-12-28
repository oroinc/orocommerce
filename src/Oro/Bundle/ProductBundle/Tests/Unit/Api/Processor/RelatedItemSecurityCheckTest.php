<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Api\Processor;

use Oro\Bundle\ApiBundle\Tests\Unit\Processor\Get\GetProcessorTestCase;
use Oro\Bundle\ProductBundle\Api\Processor\RelatedItemSecurityCheck;
use Oro\Bundle\SecurityBundle\Tests\Unit\Authorization\FakeAuthorizationChecker;

class RelatedItemSecurityCheckTest extends GetProcessorTestCase
{
    /**
     * @expectedException \Symfony\Component\Security\Core\Exception\AccessDeniedException
     */
    public function testAccessIsDeniedWhenUserDoesNotHaveCapabilityToEditRelatedProducts()
    {
        $processor = $this->getProcessor([], ['oro_related_products_edit' => false]);

        $processor->process($this->context);
        self::assertEquals([], $this->context->getSkippedGroups());
    }

    public function testAccessIsGrantedAndSecurityCheckGroupSkippedWhenUserHasCapabilityToEditRelatedProducts()
    {
        $processor = $this->getProcessor([], ['oro_related_products_edit' => true]);

        $processor->process($this->context);
        self::assertEquals(['security_check'], $this->context->getSkippedGroups());
    }

    /**
     * @dataProvider withoutProperPermissionsDataProvider
     * @expectedException \Symfony\Component\Security\Core\Exception\AccessDeniedException
     */
    public function testAccessIsDeniedWhenUserDoesNotHaveProperPermissions(array $permissions, array $isGrantedMapping)
    {
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
        self::assertEquals(['security_check'], $this->context->getSkippedGroups());
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
     * @param array $permissions
     * @param array $isGrantedMapping
     *
     * @return RelatedItemSecurityCheck
     */
    private function getProcessor(array $permissions, array $isGrantedMapping)
    {
        $authChecker = new FakeAuthorizationChecker();
        $authChecker->isGrantedMapping = $isGrantedMapping;

        return new RelatedItemSecurityCheck($authChecker, $permissions);
    }
}
