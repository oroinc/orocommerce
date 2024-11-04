<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\DataGrid\EventListener;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\EntityBundle\Provider\EntityNameProviderInterface;
use Oro\Bundle\EntityBundle\Provider\EntityNameResolver;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeFamily;
use Oro\Bundle\ProductBundle\DataGrid\EventListener\AttributeFamilyColumnOnProductsListener;

class AttributeFamilyColumnOnProductsListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var EntityNameResolver|\PHPUnit\Framework\MockObject\MockObject  */
    private $entityNameResolver;

    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject  */
    private ManagerRegistry $doctrine;

    private AttributeFamilyColumnOnProductsListener $listener;

    #[\Override]
    protected function setUp(): void
    {
        $this->entityNameResolver = $this->createMock(EntityNameResolver::class);
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->listener = new AttributeFamilyColumnOnProductsListener($this->entityNameResolver, $this->doctrine);
    }

    public function testOnBuildBefore(): void
    {
        $config = DatagridConfiguration::create([]);
        $event = new BuildBefore($this->createMock(DatagridInterface::class), $config);

        $this->entityNameResolver->expects(self::once())
            ->method('getNameDQL')
            ->with(AttributeFamily::class, 'af', EntityNameProviderInterface::FULL)
            ->willReturn('af.attributeFamily');

        $this->listener->onBuildBefore($event);

        self::assertEquals(
            ['attributeFamily' => ['label' => 'oro.product.attribute_family.label']],
            $config->offsetGetByPath('[columns]')
        );

        self::assertEquals(
            [
                'select' => ['af.attributeFamily AS attributeFamily'],
                'join' => ['left' => [['join' => 'product.attributeFamily', 'alias' => 'af']]]
            ],
            $config->offsetGetByPath('[source][query]')
        );

        self::assertEquals(
            ['attributeFamily' => ['data_name' => 'product.attributeFamily']],
            $config->offsetGetByPath('[sorters][columns]')
        );

        self::assertEquals(
            [
                'attributeFamily' => [
                    'data_name' => 'product.attributeFamily',
                   'type' => 'choice-tree',
                   'label' => 'oro.product.attribute_family.label',
                   'autocomplete_alias' => 'oro_product_families',
                   'renderedPropertyName' => 'defaultLabel',
                   'className' => AttributeFamily::class
                ]
            ],
            $config->offsetGetByPath('[filters][columns]')
        );

        self::assertEquals(
            ['attributeFamily' => ['data_name' => 'product.attributeFamily', 'column_name' => 'attribute_family']],
            $config->offsetGetByPath('[fields_acl][columns]')
        );
    }
}
