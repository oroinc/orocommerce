<?php

namespace Oro\Bundle\UPSBundle\Tests\Unit\Model;

use Oro\Bundle\UPSBundle\Model\Package;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;

class PackageTest extends \PHPUnit\Framework\TestCase
{
    use EntityTestCaseTrait;

    private const PACKAGING_TYPE_CODE = '02';
    private const DIMENSION_CODE = 'IN';
    private const DIMENSION_LENGTH = '1';
    private const DIMENSION_WIDTH = '2';
    private const DIMENSION_HEIGHT = '3';
    private const WEIGHT_CODE = 'LB';
    private const WEIGHT = '4';

    /** @var array */
    private $resultArray;

    /** @var Package */
    private $model;

    protected function setUp(): void
    {
        $this->model = new Package();
    }

    public function testGettersAndSetters()
    {
        self::assertPropertyAccessors(
            $this->model,
            [
                ['packagingTypeCode', self::PACKAGING_TYPE_CODE],
                ['weightCode', self::WEIGHT_CODE],
                ['weight', self::WEIGHT],
            ]
        );
    }

    public function testToArray()
    {
        $this->initPackage();

        self::assertEquals($this->resultArray, $this->model->toArray());
    }

    public function testToJson()
    {
        $this->initPackage();

        self::assertEquals(json_encode($this->resultArray, JSON_THROW_ON_ERROR), $this->model->toJson());
    }

    public function initPackage()
    {
        $this->model
            ->setPackagingTypeCode(self::PACKAGING_TYPE_CODE)
            ->setWeightCode(self::WEIGHT_CODE)
            ->setWeight(self::WEIGHT);

        $this->resultArray = [
            'PackagingType' => [
                'Code' => self::PACKAGING_TYPE_CODE,
            ],
            'PackageWeight' => [
                'UnitOfMeasurement' => [
                    'Code' => self::WEIGHT_CODE,
                ],
                'Weight' => self::WEIGHT,
            ],
        ];
    }

    public function testCreate()
    {
        $package = Package::create(
            self::WEIGHT_CODE,
            self::WEIGHT
        );

        self::assertEquals(self::WEIGHT_CODE, $package->getWeightCode());
        self::assertEquals(self::WEIGHT, $package->getWeight());
    }
}
