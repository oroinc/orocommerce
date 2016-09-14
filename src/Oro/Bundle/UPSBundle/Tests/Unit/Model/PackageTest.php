<?php

namespace Oro\Bundle\UPSBundle\Tests\Unit\Model;

use Oro\Bundle\UPSBundle\Model\Package;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;

class PackageTest extends \PHPUnit_Framework_TestCase
{
    use EntityTestCaseTrait;

    const PACKAGING_TYPE_CODE = '02';
    const DIMENSION_CODE = 'IN';
    const DIMENSION_LENGTH = '1';
    const DIMENSION_WIDTH = '2';
    const DIMENSION_HEIGHT = '3';
    const WEIGHT_CODE = 'LB';
    const WEIGHT = '4';

    /**
     * @var Package
     */
    protected $model;

    /**
     * @var  array
     */
    protected $resultArray;

    protected function setUp()
    {
        $this->model = new Package();
    }

    protected function tearDown()
    {
        unset($this->model);
    }

    public function testAccessors()
    {
        static::assertPropertyAccessors(
            $this->model,
            [
                ['packagingTypeCode', self::PACKAGING_TYPE_CODE],
                ['dimensionCode', self::DIMENSION_CODE],
                ['dimensionLength', self::DIMENSION_LENGTH],
                ['dimensionWidth', self::DIMENSION_WIDTH],
                ['dimensionHeight', self::DIMENSION_HEIGHT],
                ['weightCode', self::WEIGHT_CODE],
                ['weight', self::WEIGHT],
            ]
        );
    }

    public function testToArray()
    {
        $this->initPackage();

        static::assertEquals($this->resultArray, $this->model->toArray());
    }

    public function testToJson()
    {
        $this->initPackage();

        static::assertEquals(json_encode($this->resultArray), $this->model->toJson());
    }

    public function initPackage()
    {
        $this->model
            ->setPackagingTypeCode(self::PACKAGING_TYPE_CODE)
            ->setDimensionCode(self::DIMENSION_CODE)
            ->setDimensionLength(self::DIMENSION_LENGTH)
            ->setDimensionWidth(self::DIMENSION_WIDTH)
            ->setDimensionHeight(self::DIMENSION_HEIGHT)
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
            self::DIMENSION_CODE,
            self::DIMENSION_HEIGHT,
            self::DIMENSION_WIDTH,
            self::DIMENSION_LENGTH,
            self::WEIGHT_CODE,
            self::WEIGHT
        );

        $this->assertEquals(self::DIMENSION_CODE, $package->getDimensionCode());
        $this->assertEquals(self::DIMENSION_HEIGHT, $package->getDimensionHeight());
        $this->assertEquals(self::DIMENSION_WIDTH, $package->getDimensionWidth());
        $this->assertEquals(self::DIMENSION_LENGTH, $package->getDimensionLength());
        $this->assertEquals(self::WEIGHT_CODE, $package->getWeightCode());
        $this->assertEquals(self::WEIGHT, $package->getWeight());
    }
}
