<?php

namespace Oro\Bundle\TaxBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\TaxBundle\Entity\Tax;

class LoadTaxes extends AbstractFixture
{
    public const REFERENCE_PREFIX = 'tax';

    public const TAX_1 = 'TAX1';
    public const TAX_2 = 'TAX2';
    public const TAX_3 = 'TAX3';
    public const TAX_4 = 'TAX4';

    private const DATA = [
        self::TAX_1 => [
            'description' => 'Tax description 1',
            'rate'        => 0.104
        ],
        self::TAX_2 => [
            'description' => 'Tax description 2',
            'rate'        => 0.2
        ],
        self::TAX_3 => [
            'description' => 'Tax description 3',
            'rate'        => 0.075
        ],
        self::TAX_4 => [
            'description' => 'Tax description 4',
            'rate'        => 0.9
        ]
    ];

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager): void
    {
        foreach (self::DATA as $code => $item) {
            $tax = new Tax();
            $tax->setCode($code);
            $tax->setDescription($item['description']);
            $tax->setRate($item['rate']);
            $manager->persist($tax);
            $this->addReference(self::REFERENCE_PREFIX . '.' . $code, $tax);
        }
        $manager->flush();
    }
}
