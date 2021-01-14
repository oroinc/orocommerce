<?php

namespace Oro\Bundle\ProductBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;

class UpdateAttributesConfig extends AbstractFixture implements ContainerAwareInterface
{
    use MakeProductAttributesTrait;

    /**
     * @var array
     */
    private $fields = [
        'sku' => [
            'searchable' => true,
            'filterable' => true,
            'filter_by' => 'exact_value',
            'sortable' => true,
        ],
        'names' => [
            'searchable' => true,
            'filterable' => true,
            'filter_by' => 'exact_value',
            'sortable' => true,
        ],
        'descriptions' => [
            'searchable' => true,
        ],
        'shortDescriptions' => [
            'searchable' => true,
        ],
        'brand' => [
            'searchable' => true,
        ],
    ];

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $this->updateProductAttributes($this->fields);
    }
}
