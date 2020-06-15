<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\Datagrid\Extension;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\Common\MetadataObject;
use Oro\Bundle\DataGridBundle\Datagrid\Common\ResultsObject;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\ShoppingListBundle\Datagrid\Extension\MyShoppingListDatagridExtension;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Bundle\ShoppingListBundle\Entity\Repository\LineItemRepository;

class MyShoppingListDatagridExtensionTest extends \PHPUnit\Framework\TestCase
{
    /** @var LineItemRepository */
    private $repository;

    /** @var ParameterBag */
    private $parameters;

    /** @var MyShoppingListDatagridExtension */
    private $extension;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(LineItemRepository::class);

        $manager = $this->createMock(ObjectManager::class);
        $manager->expects($this->any())
            ->method('getRepository')
            ->with(LineItem::class)
            ->willReturn($this->repository);

        $registry = $this->createMock(ManagerRegistry::class);
        $registry->expects($this->any())
            ->method('getManagerForClass')
            ->with(LineItem::class)
            ->willReturn($manager);

        $this->parameters = new ParameterBag();

        $this->extension = new MyShoppingListDatagridExtension($registry);
        $this->extension->setParameters($this->parameters);
    }

    public function testIsApplicable(): void
    {
        $config = DatagridConfiguration::create(['name' => 'my-shopping-list-line-items-grid']);

        $this->assertTrue($this->extension->isApplicable($config));
    }

    public function testIsNotApplicable(): void
    {
        $config = DatagridConfiguration::create(['name' => 'shopping-list-line-items-grid']);

        $this->assertFalse($this->extension->isApplicable($config));
    }

    public function testVisitMetadata(): void
    {
        $this->parameters->set('shopping_list_id', 42);

        $data = MetadataObject::create([]);

        $this->repository->expects($this->once())
            ->method('hasEmptyMatrix')
            ->with(42)
            ->willReturn(true);

        $this->extension->visitMetadata(DatagridConfiguration::create([]), $data);

        $this->assertTrue($data->offsetGetByPath('hasEmptyMatrix'));
    }

    public function testVisitMetadataWithoutId(): void
    {
        $data = MetadataObject::create([]);

        $this->repository->expects($this->never())
            ->method('hasEmptyMatrix');

        $this->extension->visitMetadata(DatagridConfiguration::create([]), $data);

        $this->assertNull($data->offsetGetByPath('hasEmptyMatrix'));
    }

    public function testVisitResult(): void
    {
        $this->parameters->set('shopping_list_id', 42);

        $data = ResultsObject::create([]);

        $this->repository->expects($this->once())
            ->method('hasEmptyMatrix')
            ->with(42)
            ->willReturn(true);

        $this->extension->visitResult(DatagridConfiguration::create([]), $data);

        $this->assertTrue($data->offsetGetByPath('[metadata][hasEmptyMatrix]'));
    }

    public function testVisitResultWithoutId(): void
    {
        $data = ResultsObject::create([]);

        $this->repository->expects($this->never())
            ->method('hasEmptyMatrix');

        $this->extension->visitResult(DatagridConfiguration::create([]), $data);

        $this->assertNull($data->offsetGetByPath('[metadata][hasEmptyMatrix]'));
    }
}
