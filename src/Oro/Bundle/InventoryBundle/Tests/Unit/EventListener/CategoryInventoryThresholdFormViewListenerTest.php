<?php

namespace Oro\Bundle\InventoryBundle\Tests\Unit\EventListener;

use Oro\Bundle\InventoryBundle\EventListener\CategoryInventoryThresholdFormViewListener;
use Oro\Bundle\CatalogBundle\Entity\Category;

class CategoryInventoryThresholdFormViewListenerTest extends AbstractFallbackFieldsFormViewTest
{
    /** @var CategoryInventoryThresholdFormViewListener */
    protected $listener;

    protected function setUp()
    {
        parent::setUp();

        $this->listener = new CategoryInventoryThresholdFormViewListener(
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
