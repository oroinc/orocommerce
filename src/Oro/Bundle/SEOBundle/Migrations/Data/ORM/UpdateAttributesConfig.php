<?php

namespace Oro\Bundle\SEOBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\ProductBundle\Migrations\Data\ORM\MakeProductAttributesTrait;
use Oro\Component\DependencyInjection\ContainerAwareInterface;

/**
 * Updates SEO meta attribute configurations to enable searching and indexing.
 */
class UpdateAttributesConfig extends AbstractFixture implements ContainerAwareInterface
{
    use MakeProductAttributesTrait;

    /**
     * @var array
     */
    private $fields = [
        'metaDescriptions' => [
            'searchable' => true,
            'filterable' => false,
            'sortable' => false,
        ],
        'metaKeywords' => [
            'searchable' => true,
            'filterable' => false,
            'sortable' => false,
        ],
        'metaTitles' => [
            'searchable' => true,
            'filterable' => false,
            'sortable' => false,
        ],
    ];

    #[\Override]
    public function load(ObjectManager $manager)
    {
        $this->updateProductAttributes($this->fields);
    }
}
