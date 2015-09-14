<?php

namespace OroB2B\Bundle\CatalogBundle\Tests\Unit\EventListener;

use Doctrine\Bundle\DoctrineBundle\Registry;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Event\PreBuild;
use Oro\Bundle\DataGridBundle\EventListener\DatasourceBindParametersListener;

use OroB2B\Bundle\CatalogBundle\Entity\Category;
use OroB2B\Bundle\CatalogBundle\Entity\Repository\CategoryRepository;
use OroB2B\Bundle\CatalogBundle\EventListener\ProductDatagridListener;
use OroB2B\Bundle\CatalogBundle\Handler\RequestProductHandler;

class ProductDatagridListenerTest extends \PHPUnit_Framework_TestCase
{
    const DATA_CLASS = 'OroB2B\Bundle\CatalogBundle\Entity\Category';
    const QUERY_AND_PATH = '[source][query][where][and]';
    const CATEGORY_ID_ALIAS = 'productCategoryIds';

    /** @var  Registry|\PHPUnit_Framework_MockObject_MockObject */
    protected $doctrine;

    /** @var RequestProductHandler|\PHPUnit_Framework_MockObject_MockObject */
    protected $requestProductHandler;

    /** @var PreBuild|\PHPUnit_Framework_MockObject_MockObject */
    protected $event;

    /** @var  ProductDatagridListener */
    protected $productDatagridListener;

    public function setUp()
    {
        $this->doctrine = $this->getMockBuilder('Doctrine\Bundle\DoctrineBundle\Registry')
            ->disableOriginalConstructor()
            ->getMock();
        $this->requestProductHandler = $this->getMock('OroB2B\Bundle\CatalogBundle\Handler\RequestProductHandler');
        $this->event = $this->getMockBuilder('Oro\Bundle\DataGridBundle\Event\PreBuild')
            ->disableOriginalConstructor()
            ->getMock();
        $this->productDatagridListener = new ProductDatagridListener($this->doctrine, $this->requestProductHandler);
        $this->productDatagridListener->setDataClass(self::DATA_CLASS);
    }

    /**
     * @dataProvider childrenIdsDataProvider
     *
     * @param array $childrenIds
     */
    public function testOnPreBuild(array $childrenIds)
    {
        $catId = 1;
        $category = new Category();
        $this->requestProductHandler->expects($this->once())->method('getCategoryId')->willReturn($catId);

        /** @var CategoryRepository|\PHPUnit_Framework_MockObject_MockObject $repo */
        $repo = $this->getMockBuilder('OroB2B\Bundle\CatalogBundle\Entity\Repository\CategoryRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $repo->expects($this->once())->method('find')->with($catId)->willReturn($category);
        $repo->expects($this->once())->method('getChildrenIds')->with($category)->willReturn($childrenIds);
        $this->doctrine->expects($this->once())->method('getRepository')->with(self::DATA_CLASS)->willReturn($repo);

        /** @var $config DatagridConfiguration|\PHPUnit_Framework_MockObject_MockObject */
        $config = $this->getMockBuilder('Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration')
            ->disableOriginalConstructor()
            ->getMock();
        $this->event->expects($this->once())->method('getConfig')->willReturn($config);
        /** @var ParameterBag|\PHPUnit_Framework_MockObject_MockObject $params */
        $params = $this->getMock('Oro\Bundle\DataGridBundle\Datagrid\ParameterBag');
        $params->expects($this->once())
            ->method('set')
            ->with('productCategoryIds', array_merge($childrenIds, [$catId]));
        $this->event->expects($this->once())->method('getParameters')->willReturn($params);

        $config->expects($this->at(0))
            ->method('offsetSetByPath')
            ->with(self::QUERY_AND_PATH, [sprintf('productCategory.id IN (:%s)', self::CATEGORY_ID_ALIAS)]);
        $config->expects($this->at(1))
            ->method('offsetSetByPath')
            ->with(DatasourceBindParametersListener::DATASOURCE_BIND_PARAMETERS_PATH, [self::CATEGORY_ID_ALIAS]);
        $this->productDatagridListener->onPreBuild($this->event);
    }

    /**
     * @return array
     */
    public function childrenIdsDataProvider()
    {
        return [
            ['withChildren' => [2, 3]],
            ['withoutChildren' => []],
        ];
    }

    public function testOnPreBuildWithoutExistingCategory()
    {
        $catId = 1;
        $this->requestProductHandler->expects($this->once())->method('getCategoryId')->willReturn($catId);
        /** @var CategoryRepository|\PHPUnit_Framework_MockObject_MockObject $repo */
        $repo = $this->getMockBuilder('OroB2B\Bundle\CatalogBundle\Entity\Repository\CategoryRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $repo->expects($this->once())->method('find')->with($catId)->willReturn(null);
        $this->doctrine->expects($this->once())->method('getRepository')->with(self::DATA_CLASS)->willReturn($repo);
        $repo->expects($this->never())->method('getChildrenIds');
        $this->productDatagridListener->onPreBuild($this->event);
    }

    public function testOnPreBuildWithoutCategoryId()
    {
        $this->requestProductHandler->expects($this->once())->method('getCategoryId')->willReturn(false);
        $this->doctrine->expects($this->never())->method('getRepository');
        $this->productDatagridListener->onPreBuild($this->event);
    }
}
