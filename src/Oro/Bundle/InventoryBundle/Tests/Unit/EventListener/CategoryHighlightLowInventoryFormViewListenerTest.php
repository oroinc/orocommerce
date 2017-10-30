<?php

namespace Oro\Bundle\InventoryBundle\Tests\Unit\EventListener;

use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\InventoryBundle\EventListener\CategoryHighlightLowInventoryFormViewListener;

class CategoryHighlightLowInventoryFormViewListenerTest extends AbstractFallbackFieldsFormViewTest
{
    /** @var CategoryHighlightLowInventoryFormViewListener */
    protected $listener;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->listener = new CategoryHighlightLowInventoryFormViewListener(
            $this->requestStack,
            $this->doctrine,
            $this->translator
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        unset($this->listener);

        parent::tearDown();
    }

    /**
     * @return void
     */
    protected function callTestMethod()
    {
        $this->listener->onCategoryEdit($this->event);
    }

    /**
     * @return array
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
