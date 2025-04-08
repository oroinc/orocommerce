<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\Api\RestJsonApi;

use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Oro\Bundle\ProductBundle\Tests\Functional\Api\DataFixtures\LoadProductUnits;
use Oro\Bundle\ProductBundle\Tests\Functional\Api\DataFixtures\LoadProductUnitWithTranslations;

/**
 * @dbIsolationPerTest
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class ProductUnitTest extends RestJsonApiTestCase
{
    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures([LoadProductUnits::class, LoadProductUnitWithTranslations::class]);
    }

    public function testGetList(): void
    {
        $response = $this->cget(['entity' => 'productunits']);
        $this->assertResponseContains('cget_product_units.yml', $response);
    }

    public function testCreate(): void
    {
        $response = $this->post(
            ['entity' => 'productunits'],
            'create_product_unit.yml'
        );

        $this->assertResponseContains('create_product_unit.yml', $response);

        $translator = self::getContainer()->get('translator');
        self::assertEquals('test', $translator->trans('oro.product_unit.test_unit.label.full'));
        self::assertEquals('tests', $translator->trans('oro.product_unit.test_unit.label.full_plural'));
        self::assertEquals('tst', $translator->trans('oro.product_unit.test_unit.label.short'));
        self::assertEquals('tsts', $translator->trans('oro.product_unit.test_unit.label.short_plural'));

        self::assertEquals(
            '{0} none|{1} %count% test|]1,Inf] %count% tests',
            $translator->trans('oro.product_unit.test_unit.value.full')
        );
        self::assertEquals('%count% test', $translator->trans('oro.product_unit.test_unit.value.full_fraction'));
        self::assertEquals('%count% tests', $translator->trans('oro.product_unit.test_unit.value.full_fraction_gt_1'));
        self::assertEquals(
            '{0} none|{1} %count% tst|]1,Inf] %count% tsts',
            $translator->trans('oro.product_unit.test_unit.value.short')
        );
        self::assertEquals('%count% tst', $translator->trans('oro.product_unit.test_unit.value.short_fraction'));
        self::assertEquals('%count% tsts', $translator->trans('oro.product_unit.test_unit.value.short_fraction_gt_1'));

        self::assertEquals(
            'test',
            $translator->trans('oro.product.product_unit.test_unit.label.full', [], 'jsmessages')
        );
        self::assertEquals(
            'tests',
            $translator->trans('oro.product.product_unit.test_unit.label.full_plural', [], 'jsmessages')
        );
        self::assertEquals(
            'tst',
            $translator->trans('oro.product.product_unit.test_unit.label.short', [], 'jsmessages')
        );
        self::assertEquals(
            'tsts',
            $translator->trans('oro.product.product_unit.test_unit.label.short_plural', [], 'jsmessages')
        );

        self::assertEquals(
            '{0} none|]0,1] {{ count }} test|]1,Inf]{{ count }} tests',
            $translator->trans('oro.product.product_unit.test_unit.value.full', [], 'jsmessages')
        );
        self::assertEquals(
            '{0} none|]0,1] {{ count }} tst|]1,Inf]{{ count }} tsts',
            $translator->trans('oro.product.product_unit.test_unit.value.short', [], 'jsmessages')
        );
        self::assertEquals(
            '{0} none|]0,1] tst|]1,Inf] tsts',
            $translator->trans('oro.product.product_unit.test_unit.value.label', [], 'jsmessages')
        );
    }

    public function testGet(): void
    {
        $response = $this->get(
            ['entity' => 'productunits', 'id' => '<toString(@day->code)>']
        );

        $this->assertResponseContains('get_product_unit.yml', $response);

        $translator = self::getContainer()->get('translator');
        self::assertEquals(
            '{0} none|{1} %count% day|]1,Inf] %count% days',
            $translator->trans('oro.product_unit.day.value.full')
        );
        self::assertEquals(
            '{0} none|{1} %count% d|]1,Inf] %count% ds',
            $translator->trans('oro.product_unit.day.value.short')
        );
        self::assertEquals(
            '{0} none|]0,1] {{ count }} day|]1,Inf]{{ count }} days',
            $translator->trans('oro.product.product_unit.day.value.full', [], 'jsmessages')
        );
        self::assertEquals(
            '{0} none|]0,1] {{ count }} d|]1,Inf]{{ count }} ds',
            $translator->trans('oro.product.product_unit.day.value.short', [], 'jsmessages')
        );
    }

    public function testUpdateWithoutTranslations(): void
    {
        $response = $this->patch(
            ['entity' => 'productunits', 'id' => '<toString(@day->code)>'],
            [
                'data' => [
                    'type' => 'productunits',
                    'id' => '<toString(@day->code)>',
                    'attributes' => [
                        'defaultPrecision' => 0
                    ]
                ]
            ]
        );

        $this->assertResponseContains('update_product_unit_without_translations.yml', $response);

        $translator = self::getContainer()->get('translator');
        self::assertEquals(
            '{0} none|{1} %count% day|]1,Inf] %count% days',
            $translator->trans('oro.product_unit.day.value.full')
        );
        self::assertEquals(
            '{0} none|{1} %count% d|]1,Inf] %count% ds',
            $translator->trans('oro.product_unit.day.value.short')
        );
        self::assertEquals(
            '{0} none|]0,1] {{ count }} day|]1,Inf]{{ count }} days',
            $translator->trans('oro.product.product_unit.day.value.full', [], 'jsmessages')
        );
        self::assertEquals(
            '{0} none|]0,1] {{ count }} d|]1,Inf]{{ count }} ds',
            $translator->trans('oro.product.product_unit.day.value.short', [], 'jsmessages')
        );
    }

    public function testUpdateFullLabels(): void
    {
        $response = $this->patch(
            ['entity' => 'productunits', 'id' => '<toString(@day->code)>'],
            [
                'data' => [
                    'type' => 'productunits',
                    'id' => '<toString(@day->code)>',
                    'attributes' => [
                        'label' => 'day_upd',
                        'pluralLabel' => 'days_upd'
                    ]
                ]
            ]
        );

        $this->assertResponseContains('update_product_unit_full_labels.yml', $response);

        $translator = self::getContainer()->get('translator');
        self::assertEquals(
            '{0} none|{1} %count% day_upd|]1,Inf] %count% days_upd',
            $translator->trans('oro.product_unit.day.value.full')
        );
        self::assertEquals(
            '{0} none|{1} %count% d|]1,Inf] %count% ds',
            $translator->trans('oro.product_unit.day.value.short')
        );
        self::assertEquals(
            '{0} none|]0,1] {{ count }} day_upd|]1,Inf]{{ count }} days_upd',
            $translator->trans('oro.product.product_unit.day.value.full', [], 'jsmessages')
        );
        self::assertEquals(
            '{0} none|]0,1] {{ count }} d|]1,Inf]{{ count }} ds',
            $translator->trans('oro.product.product_unit.day.value.short', [], 'jsmessages')
        );
    }

    public function testUpdateShortLabels(): void
    {
        $response = $this->patch(
            ['entity' => 'productunits', 'id' => '<toString(@day->code)>'],
            [
                'data' => [
                    'type' => 'productunits',
                    'id' => '<toString(@day->code)>',
                    'attributes' => [
                        'shortLabel' => 'd_upd',
                        'shortPluralLabel' => 'ds_upd'
                    ]
                ]
            ]
        );

        $this->assertResponseContains('update_product_unit_short_labels.yml', $response);

        $translator = self::getContainer()->get('translator');
        self::assertEquals(
            '{0} none|{1} %count% day|]1,Inf] %count% days',
            $translator->trans('oro.product_unit.day.value.full')
        );
        self::assertEquals(
            '{0} none|{1} %count% d_upd|]1,Inf] %count% ds_upd',
            $translator->trans('oro.product_unit.day.value.short')
        );
        self::assertEquals(
            '{0} none|]0,1] {{ count }} day|]1,Inf]{{ count }} days',
            $translator->trans('oro.product.product_unit.day.value.full', [], 'jsmessages')
        );
        self::assertEquals(
            '{0} none|]0,1] {{ count }} d_upd|]1,Inf]{{ count }} ds_upd',
            $translator->trans('oro.product.product_unit.day.value.short', [], 'jsmessages')
        );
    }

    public function testTryToCreateWithoutLabelFields(): void
    {
        $response = $this->post(
            ['entity' => 'productunits'],
            [
                'data' => [
                    'type' => 'productunits',
                    'id' => 'test',
                    'attributes' => [
                        'defaultPrecision' => 0
                    ]
                ]
            ],
            [],
            false
        );

        $this->assertResponseValidationErrors(
            [
                [
                    'title' => 'not blank constraint',
                    'detail' => 'This value should not be blank.',
                    'source' => ['pointer' => '/data/attributes/label']
                ],
                [
                    'title' => 'not blank constraint',
                    'detail' => 'This value should not be blank.',
                    'source' => ['pointer' => '/data/attributes/shortLabel']
                ],
                [
                    'title' => 'not blank constraint',
                    'detail' => 'This value should not be blank.',
                    'source' => ['pointer' => '/data/attributes/pluralLabel']
                ],
                [
                    'title' => 'not blank constraint',
                    'detail' => 'This value should not be blank.',
                    'source' => ['pointer' => '/data/attributes/shortPluralLabel']
                ]
            ],
            $response
        );
    }

    public function testTryToCreateWithoutLabelField(): void
    {
        $response = $this->post(
            ['entity' => 'productunits'],
            [
                'data' => [
                    'type' => 'productunits',
                    'id' => 'test',
                    'attributes' => [
                        'defaultPrecision' => 0,
                        'shortLabel' => 'tst',
                        'pluralLabel' => 'tests',
                        'shortPluralLabel' => 'tsts'
                    ]
                ]
            ],
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title' => 'not blank constraint',
                'detail' => 'This value should not be blank.',
                'source' => ['pointer' => '/data/attributes/label']
            ],
            $response
        );
    }

    public function testTryToCreateWithEmptyLabelField(): void
    {
        $response = $this->post(
            ['entity' => 'productunits'],
            [
                'data' => [
                    'type' => 'productunits',
                    'id' => 'test',
                    'attributes' => [
                        'defaultPrecision' => 0,
                        'label' => ' ',
                        'shortLabel' => 'tst',
                        'pluralLabel' => 'tests',
                        'shortPluralLabel' => 'tsts'
                    ]
                ]
            ],
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title' => 'not blank constraint',
                'detail' => 'This value should not be blank.',
                'source' => ['pointer' => '/data/attributes/label']
            ],
            $response
        );
    }

    public function testTryToCreateWithNullLabelField(): void
    {
        $response = $this->post(
            ['entity' => 'productunits'],
            [
                'data' => [
                    'type' => 'productunits',
                    'id' => 'test',
                    'attributes' => [
                        'defaultPrecision' => 0,
                        'label' => null,
                        'shortLabel' => 'tst',
                        'pluralLabel' => 'tests',
                        'shortPluralLabel' => 'tsts'
                    ]
                ]
            ],
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title' => 'not blank constraint',
                'detail' => 'This value should not be blank.',
                'source' => ['pointer' => '/data/attributes/label']
            ],
            $response
        );
    }

    public function testTryToUpdateWithNullLabelField(): void
    {
        $response = $this->patch(
            ['entity' => 'productunits', 'id' => '<toString(@day->code)>'],
            [
                'data' => [
                    'type' => 'productunits',
                    'id' => '<toString(@day->code)>',
                    'attributes' => [
                        'label' => null
                    ]
                ]
            ],
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title' => 'not blank constraint',
                'detail' => 'This value should not be blank.',
                'source' => ['pointer' => '/data/attributes/label']
            ],
            $response
        );
    }
}
