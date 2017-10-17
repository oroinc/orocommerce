<?php

namespace Oro\Bundle\CatalogBundle\Tests\Unit\Datagrid\Extension;

use Oro\Bundle\CatalogBundle\Datagrid\Extension\CategoryCountsExtension;
use Oro\Bundle\CatalogBundle\Entity\Repository\CategoryRepository;
use Oro\Bundle\CatalogBundle\Search\ProductRepository;
use Oro\Bundle\DataGridBundle\Datagrid\Manager;
use Oro\Bundle\DataGridBundle\Datagrid\RequestParameterBagFactory;

class CategoryCountsExtensionTest extends \PHPUnit_Framework_TestCase
{
    /** @var Manager */
    protected $datagridManager;

    /** @var RequestParameterBagFactory */
    protected $parametersFactory;

    /** @var CategoryRepository */
    protected $categoryRepository;

    /** @var ProductRepository */
    protected $productSearchRepository;

    /** @var CategoryCountsExtension */
    protected $extension;

    protected function setUp()
    {
        $this->extension = new CategoryCountsExtension(
            $this->datagridManager,
            $this->parametersFactory,
            $this->categoryRepository,
            $this->productSearchRepository
        );
    }

    public function testIsApplicable()
    {

    }

    public function testVisitMetadata()
    {

    }
}
