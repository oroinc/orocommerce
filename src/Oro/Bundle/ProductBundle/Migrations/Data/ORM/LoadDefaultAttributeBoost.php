<?php

namespace Oro\Bundle\ProductBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;

/**
 * Attributes 'sku' and 'names' should have higher search priority by default.
 */
class LoadDefaultAttributeBoost extends AbstractFixture implements ContainerAwareInterface
{
    use MakeProductAttributesTrait;

    /**
     * @var array
     */
    private $fields = [
        'sku' => [
            'search_boost' => 5,
        ],
        'names' => [
            'search_boost' => 3,
        ],
    ];

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $this->updateProductAttributes($this->fields);

        $this->getConfigManager()->flush();
    }
}
