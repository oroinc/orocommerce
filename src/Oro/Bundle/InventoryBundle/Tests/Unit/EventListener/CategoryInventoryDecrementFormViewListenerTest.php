<?php

namespace Oro\Bundle\InventoryBundle\Tests\Unit\EventListener;

use Oro\Bundle\InventoryBundle\EventListener\CategoryInventoryDecrementFormViewListener;
use Oro\Bundle\CatalogBundle\Entity\Category;

class CategoryInventoryDecrementFormViewListenerTest extends AbstractFallbackFieldsFormViewTest
{
    /** @var CategoryInventoryDecrementFormViewListener */
    protected $listener;

    protected function setUp()
    {
        parent::setUp();

        $this->listener = new CategoryInventoryDecrementFormViewListener(
            $this->requestStack,
            $this->doctrine,
            $this->translator
        );
    }

    protected function tearDown()
    {
        unset($this->listener);

        parent::tearDown();
    }

    /**
     * {@inheritdoc}
     */
    protected function callTestMethod()
    {
        $this->listener->onCategoryEdit($this->event);
    }

    /**
     * {@inheritdoc}
     */
    protected function getExpectedScrollData()
    {
        return ['dataBlocks' => [1 => ['title' => 'oro.catalog.sections.default_options.trans']]];
    }

    /**
     * @return Category
     */
    protected function getEntity()
    {
        return new Category();
    }
}
