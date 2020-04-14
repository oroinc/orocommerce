<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\Api\RestJsonApi;

use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Oro\Bundle\ProductBundle\Tests\Functional\Api\DataFixtures\LoadProductUnits;
use Oro\Bundle\ProductBundle\Tests\Functional\Api\DataFixtures\LoadProductUnitWithTranslations;
use Symfony\Component\HttpFoundation\Response;

/**
 * @dbIsolationPerTest
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 */
class ProductUnitTest extends RestJsonApiTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures([LoadProductUnits::class, LoadProductUnitWithTranslations::class]);
    }

    public function testGetList()
    {
        $response = $this->cget(['entity' => 'productunits']);
        $this->assertResponseContains('cget_product_units.yml', $response);
    }

    public function testCreate()
    {
        $response = $this->post(
            ['entity' => 'productunits'],
            'create_product_unit.yml'
        );

        $this->assertResponseContains('create_product_unit.yml', $response);

        $translator = $this->getContainer()->get('translator');
        $this->assertEquals('test', $translator->trans('oro.product_unit.test_unit.label.full'));
        $this->assertEquals('tests', $translator->trans('oro.product_unit.test_unit.label.full_plural'));
        $this->assertEquals('tst', $translator->trans('oro.product_unit.test_unit.label.short'));
        $this->assertEquals('tsts', $translator->trans('oro.product_unit.test_unit.label.short_plural'));

        $this->assertEquals(
            '{0} none|{1} %count% test|]1,Inf] %count% tests',
            $translator->trans('oro.product_unit.test_unit.value.full')
        );
        $this->assertEquals('%count% test', $translator->trans('oro.product_unit.test_unit.value.full_fraction'));
        $this->assertEquals('%count% tests', $translator->trans('oro.product_unit.test_unit.value.full_fraction_gt_1'));
        $this->assertEquals(
            '{0} none|{1} %count% tst|]1,Inf] %count% tsts',
            $translator->trans('oro.product_unit.test_unit.value.short')
        );
        $this->assertEquals('%count% tst', $translator->trans('oro.product_unit.test_unit.value.short_fraction'));
        $this->assertEquals('%count% tsts', $translator->trans('oro.product_unit.test_unit.value.short_fraction_gt_1'));

        $this->assertEquals(
            'test',
            $translator->trans('oro.product.product_unit.test_unit.label.full', [], 'jsmessages')
        );
        $this->assertEquals(
            'tests',
            $translator->trans('oro.product.product_unit.test_unit.label.full_plural', [], 'jsmessages')
        );
        $this->assertEquals(
            'tst',
            $translator->trans('oro.product.product_unit.test_unit.label.short', [], 'jsmessages')
        );
        $this->assertEquals(
            'tsts',
            $translator->trans('oro.product.product_unit.test_unit.label.short_plural', [], 'jsmessages')
        );

        $this->assertEquals(
            '{0} none|]0,1] {{ count }} test|]1,Inf]{{ count }} tests',
            $translator->trans('oro.product.product_unit.test_unit.value.full', [], 'jsmessages')
        );
        $this->assertEquals(
            '{0} none|]0,1] {{ count }} tst|]1,Inf]{{ count }} tsts',
            $translator->trans('oro.product.product_unit.test_unit.value.short', [], 'jsmessages')
        );
        $this->assertEquals(
            '{0} none|]0,1] tst|]1,Inf] tsts',
            $translator->trans('oro.product.product_unit.test_unit.value.label', [], 'jsmessages')
        );
    }

    public function testGet()
    {
        $response = $this->get(
            ['entity' => 'productunits', 'id' => '<toString(@day->code)>']
        );

        $this->assertResponseContains('get_product_unit.yml', $response);

        $translator = $this->getContainer()->get('translator');
        $this->assertEquals(
            '{0} none|{1} %count% day|]1,Inf] %count% days',
            $translator->trans('oro.product_unit.day.value.full')
        );
        $this->assertEquals(
            '{0} none|{1} %count% d|]1,Inf] %count% ds',
            $translator->trans('oro.product_unit.day.value.short')
        );
        $this->assertEquals(
            '{0} none|]0,1] {{ count }} day|]1,Inf]{{ count }} days',
            $translator->trans('oro.product.product_unit.day.value.full', [], 'jsmessages')
        );
        $this->assertEquals(
            '{0} none|]0,1] {{ count }} d|]1,Inf]{{ count }} ds',
            $translator->trans('oro.product.product_unit.day.value.short', [], 'jsmessages')
        );
    }

    public function testUpdateWithoutTranslations()
    {
        $response = $this->patch(
            ['entity' => 'productunits', 'id' => '<toString(@day->code)>'],
            ['data' => [
                'type'       => 'productunits',
                'id'         => '<toString(@day->code)>',
                'attributes' => [
                    'defaultPrecision' => 0
                ]
            ]]
        );

        $this->assertResponseContains('update_product_unit_without_translations.yml', $response);

        $translator = $this->getContainer()->get('translator');
        $this->assertEquals(
            '{0} none|{1} %count% day|]1,Inf] %count% days',
            $translator->trans('oro.product_unit.day.value.full')
        );
        $this->assertEquals(
            '{0} none|{1} %count% d|]1,Inf] %count% ds',
            $translator->trans('oro.product_unit.day.value.short')
        );
        $this->assertEquals(
            '{0} none|]0,1] {{ count }} day|]1,Inf]{{ count }} days',
            $translator->trans('oro.product.product_unit.day.value.full', [], 'jsmessages')
        );
        $this->assertEquals(
            '{0} none|]0,1] {{ count }} d|]1,Inf]{{ count }} ds',
            $translator->trans('oro.product.product_unit.day.value.short', [], 'jsmessages')
        );
    }

    public function testUpdateFullLabels()
    {
        $response = $this->patch(
            ['entity' => 'productunits', 'id' => '<toString(@day->code)>'],
            ['data' => [
                'type'       => 'productunits',
                'id'         => '<toString(@day->code)>',
                'attributes' => [
                    'label'       => 'day_upd',
                    'pluralLabel' => 'days_upd',
                ]
            ]]
        );

        $this->assertResponseContains('update_product_unit_full_labels.yml', $response);

        $translator = $this->getContainer()->get('translator');
        $this->assertEquals(
            '{0} none|{1} %count% day_upd|]1,Inf] %count% days_upd',
            $translator->trans('oro.product_unit.day.value.full')
        );
        $this->assertEquals(
            '{0} none|{1} %count% d|]1,Inf] %count% ds',
            $translator->trans('oro.product_unit.day.value.short')
        );
        $this->assertEquals(
            '{0} none|]0,1] {{ count }} day_upd|]1,Inf]{{ count }} days_upd',
            $translator->trans('oro.product.product_unit.day.value.full', [], 'jsmessages')
        );
        $this->assertEquals(
            '{0} none|]0,1] {{ count }} d|]1,Inf]{{ count }} ds',
            $translator->trans('oro.product.product_unit.day.value.short', [], 'jsmessages')
        );
    }

    public function testUpdateShortLabels()
    {
        $response = $this->patch(
            ['entity' => 'productunits', 'id' => '<toString(@day->code)>'],
            ['data' => [
                'type'       => 'productunits',
                'id'         => '<toString(@day->code)>',
                'attributes' => [
                    'shortLabel'       => 'd_upd',
                    'shortPluralLabel' => 'ds_upd',
                ]
            ]]
        );

        $this->assertResponseContains('update_product_unit_short_labels.yml', $response);

        $translator = $this->getContainer()->get('translator');
        $this->assertEquals(
            '{0} none|{1} %count% day|]1,Inf] %count% days',
            $translator->trans('oro.product_unit.day.value.full')
        );
        $this->assertEquals(
            '{0} none|{1} %count% d_upd|]1,Inf] %count% ds_upd',
            $translator->trans('oro.product_unit.day.value.short')
        );
        $this->assertEquals(
            '{0} none|]0,1] {{ count }} day|]1,Inf]{{ count }} days',
            $translator->trans('oro.product.product_unit.day.value.full', [], 'jsmessages')
        );
        $this->assertEquals(
            '{0} none|]0,1] {{ count }} d_upd|]1,Inf]{{ count }} ds_upd',
            $translator->trans('oro.product.product_unit.day.value.short', [], 'jsmessages')
        );
    }

    public function testTryToCreateWithoutLabelFields()
    {
        $response = $this->post(
            ['entity' => 'productunits'],
            ['data' => [
                'type'       => 'productunits',
                'id'         => 'test',
                'attributes' => [
                    'defaultPrecision' => 0
                ]
            ]],
            [],
            false
        );

        $this->assertResponseContains('create_product_unit_validation_erors.yml', $response);
        $this->assertResponseStatusCodeEquals($response, Response::HTTP_BAD_REQUEST);
    }

    public function testTryToCreateWithoutLabelField()
    {
        $response = $this->post(
            ['entity' => 'productunits'],
            ['data' => [
                'type'       => 'productunits',
                'id'         => 'test',
                'attributes' => [
                    'defaultPrecision' => 0,
                    'shortLabel'       => 'tst',
                    'pluralLabel'      => 'tests',
                    'shortPluralLabel' => 'tsts'
                ]
            ]],
            [],
            false
        );

        $this->assertResponseContains(
            [
                'errors' => [
                    [
                        'status' => '400',
                        'title'  => 'not blank constraint',
                        'detail' => 'This value should not be blank.',
                        'source' => ['pointer' => '/data/attributes/label']
                    ]
                ]
            ],
            $response
        );
        $this->assertResponseStatusCodeEquals($response, Response::HTTP_BAD_REQUEST);
    }

    public function testTryToCreateWithEmptyLabelField()
    {
        $response = $this->post(
            ['entity' => 'productunits'],
            ['data' => [
                'type'       => 'productunits',
                'id'         => 'test',
                'attributes' => [
                    'defaultPrecision' => 0,
                    'label' => ' ',
                    'shortLabel'       => 'tst',
                    'pluralLabel'      => 'tests',
                    'shortPluralLabel' => 'tsts'
                ]
            ]],
            [],
            false
        );

        $this->assertResponseContains(
            [
                'errors' => [
                    [
                        'status' => '400',
                        'title'  => 'not blank constraint',
                        'detail' => 'This value should not be blank.',
                        'source' => ['pointer' => '/data/attributes/label']
                    ]
                ]
            ],
            $response
        );
        $this->assertResponseStatusCodeEquals($response, Response::HTTP_BAD_REQUEST);
    }

    public function testTryToCreateWithNullLabelField()
    {
        $response = $this->post(
            ['entity' => 'productunits'],
            ['data' => [
                'type'       => 'productunits',
                'id'         => 'test',
                'attributes' => [
                    'defaultPrecision' => 0,
                    'label' => null,
                    'shortLabel'       => 'tst',
                    'pluralLabel'      => 'tests',
                    'shortPluralLabel' => 'tsts'
                ]
            ]],
            [],
            false
        );

        $this->assertResponseContains(
            [
                'errors' => [
                    [
                        'status' => '400',
                        'title'  => 'not blank constraint',
                        'detail' => 'This value should not be blank.',
                        'source' => ['pointer' => '/data/attributes/label']
                    ]
                ]
            ],
            $response
        );
        $this->assertResponseStatusCodeEquals($response, Response::HTTP_BAD_REQUEST);
    }

    public function testTryToUpdateWithNullLabelField()
    {
        $response = $this->patch(
            ['entity' => 'productunits', 'id' => '<toString(@day->code)>'],
            ['data' => [
                'type'       => 'productunits',
                'id'         => '<toString(@day->code)>',
                'attributes' => [
                    'label'       => null
                ]
            ]],
            [],
            false
        );

        $this->assertResponseContains(
            [
                'errors' => [
                    [
                        'status' => '400',
                        'title'  => 'not blank constraint',
                        'detail' => 'This value should not be blank.',
                        'source' => ['pointer' => '/data/attributes/label']
                    ]
                ]
            ],
            $response
        );
        $this->assertResponseStatusCodeEquals($response, Response::HTTP_BAD_REQUEST);
    }
}
