<?php

namespace Oro\Bundle\DPDBundle\Tests\Unit\Entity;

use Oro\Bundle\DPDBundle\Entity\Rate;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\DPDBundle\Entity\ShippingService;
use Oro\Bundle\DPDBundle\Entity\DPDTransport;
use Oro\Bundle\ShippingBundle\Entity\WeightUnit;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\ParameterBag;

class DPDTransportTest extends \PHPUnit_Framework_TestCase
{
    use EntityTestCaseTrait;
    use EntityTrait;

    public function testAccessors()
    {
        static::assertPropertyAccessors(new DPDTransport(), [
            ['liveMode', false],
            ['cloudUserId', 'some string'],
            ['cloudUserToken', 'some string'],
            ['unitOfWeight', new WeightUnit()],
            ['ratePolicy', DPDTransport::FLAT_RATE_POLICY],
            ['flatRatePriceValue', '1.000'],
            ['ratesCsv', new File('path', false)],
            ['labelSize', 'some string'],
            ['labelStartPosition', 'some string'],
            ['invalidateCacheAt', new \DateTime('2020-01-01')],
        ]);
        static::assertPropertyCollections(new DPDTransport(), [
            ['applicableShippingServices', new ShippingService()],
            ['rates', new Rate()],
            ['labels', new LocalizedFallbackValue()],
        ]);
    }

    public function testGetSettingsBag()
    {
        /** @var DPDTransport $entity */
        $entity = $this->getEntity(
            'Oro\Bundle\DPDBundle\Entity\DPDTransport',
            [
                'liveMode' => false,
                'cloudUserId' => 'some cloud user id',
                'cloudUserToken' => 'some cloud user token',
                'unitOfWeight' => ((new WeightUnit())->setCode('kg')),
                'ratePolicy' => DPDTransport::FLAT_RATE_POLICY,
                'flatRatePriceValue' => '1.000',
                'labelSize' => DPDTransport::PDF_A4_LABEL_SIZE,
                'labelStartPosition' => DPDTransport::UPPERLEFT_LABEL_START_POSITION,
                'invalidate_cache_at' => new \DateTime('2020-01-01'),
                'applicableShippingServices' => [new ShippingService()],
                'labels' => [(new LocalizedFallbackValue())->setString('DPD')],
            ]
        );

        /** @var ParameterBag $result */
        $result = $entity->getSettingsBag();

        static::assertFalse($result->get('live_mode'));
        static::assertEquals('some cloud user id', $result->get('cloud_user_id'));
        static::assertEquals('some cloud user token', $result->get('cloud_user_token'));
        static::assertEquals(((new WeightUnit())->setCode('kg')), $result->get('unit_of_weight'));
        static::assertEquals(DPDTransport::FLAT_RATE_POLICY, $result->get('rate_policy'));
        static::assertEquals('1.000', $result->get('flat_rate_price_value'));
        static::assertEquals(DPDTransport::PDF_A4_LABEL_SIZE, $result->get('label_size'));
        static::assertEquals(DPDTransport::UPPERLEFT_LABEL_START_POSITION, $result->get('label_start_position'));
        static::assertEquals(new \DateTime('2020-01-01'), $result->get('invalidate_cache_at'));

        static::assertEquals(
            $result->get('applicable_shipping_services'),
            $entity->getApplicableShippingServices()->toArray()
        );
        static::assertEquals(
            $result->get('rates'),
            $entity->getRates()->toArray()
        );
        static::assertEquals(
            $result->get('labels'),
            $entity->getLabels()->toArray()
        );
    }
}
