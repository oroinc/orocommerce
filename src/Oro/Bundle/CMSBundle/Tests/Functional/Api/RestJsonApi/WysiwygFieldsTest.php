<?php

namespace Oro\Bundle\CMSBundle\Tests\Functional\Api\RestJsonApi;

use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;

/**
 * @dbIsolationPerTest
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class WysiwygFieldsTest extends RestJsonApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->loadFixtures([
            '@OroCMSBundle/Tests/Functional/Api/DataFixtures/localized_fallback_values.yml'
        ]);
    }

    public function testGet(): void
    {
        $this->enableTwig();

        $response = $this->get(
            ['entity' => 'localizedfallbackvalues', 'id' => '<toString(@test_value_with_wysiwyg->id)>']
        );

        $this->assertResponseContains(
            [
                'data' => [
                    'type'       => 'localizedfallbackvalues',
                    'id'         => '<toString(@test_value_with_wysiwyg->id)>',
                    'attributes' => [
                        'wysiwyg'  => [
                            'value'         => 'Content. Twig Expr: "{{ " test "|trim }}".',
                            'style'         => '.test {color: {{ " red "|trim }}}',
                            'properties'    => ['param' => 'value'],
                            'valueRendered' => '<style type="text/css">.test {color: red}</style>'
                                . 'Content. Twig Expr: "test".'
                        ],
                        'fallback' => null,
                        'string'   => null,
                        'text'     => 'Test Value'
                    ]
                ]
            ],
            $response
        );
    }

    public function testGetWithEmptyValueForWysiwygField(): void
    {
        $response = $this->get(
            ['entity' => 'localizedfallbackvalues', 'id' => '<toString(@test_value_with_empty_wysiwyg->id)>']
        );

        $this->assertResponseContains(
            [
                'data' => [
                    'type'       => 'localizedfallbackvalues',
                    'id'         => '<toString(@test_value_with_empty_wysiwyg->id)>',
                    'attributes' => [
                        'wysiwyg'  => null,
                        'fallback' => null,
                        'string'   => null,
                        'text'     => 'Test Value'
                    ]
                ]
            ],
            $response
        );
    }

    public function testGetWithNullValueForWysiwygField(): void
    {
        $response = $this->get(
            ['entity' => 'localizedfallbackvalues', 'id' => '<toString(@test_value_without_wysiwyg->id)>']
        );

        $this->assertResponseContains(
            [
                'data' => [
                    'type'       => 'localizedfallbackvalues',
                    'id'         => '<toString(@test_value_without_wysiwyg->id)>',
                    'attributes' => [
                        'wysiwyg'  => null,
                        'fallback' => null,
                        'string'   => null,
                        'text'     => 'Test Value'
                    ]
                ]
            ],
            $response
        );
    }

    public function testCreateWithWysiwygField(): void
    {
        $this->enableTwig();

        $data = [
            'data' => [
                'type'       => 'localizedfallbackvalues',
                'attributes' => [
                    'wysiwyg'  => [
                        'value'      => 'Content. Twig Expr: "{{ " test "|trim }}".',
                        'style'      => '.test {color: red}',
                        'properties' => ['param' => 'value']
                    ],
                    'fallback' => null,
                    'string'   => null,
                    'text'     => 'text'
                ]
            ]
        ];
        $response = $this->post(['entity' => 'localizedfallbackvalues'], $data);

        $this->assertResponseContains($data, $response);
    }

    public function testCreateWithWysiwygFieldAndRenderedWysiwygFieldThatShouldBeIgnored(): void
    {
        $this->enableTwig();

        $data = [
            'data' => [
                'type'       => 'localizedfallbackvalues',
                'attributes' => [
                    'wysiwyg'  => [
                        'value'         => 'Content. Twig Expr: "{{ " test "|trim }}".',
                        'style'         => '.test {color: red}',
                        'properties'    => ['param' => 'value'],
                        'valueRendered' => '<style type="text/css">.another {color: blue}</style>'
                            . 'Another Content. Twig Expr: "test".'
                    ],
                    'fallback' => null,
                    'string'   => null,
                    'text'     => 'text'
                ]
            ]
        ];
        $response = $this->post(['entity' => 'localizedfallbackvalues'], $data);

        $expectedData = $data;
        $expectedData['data']['attributes']['wysiwyg']['valueRendered'] =
            '<style type="text/css">.test {color: red}</style>Content. Twig Expr: "test".';
        $this->assertResponseContains($expectedData, $response);
    }

    public function testCreateWithInvalidWysiwygField(): void
    {
        $this->enableTwig();

        $data = [
            'data' => [
                'type'       => 'localizedfallbackvalues',
                'attributes' => [
                    'wysiwyg'  => [
                        'value'      => 'Content',
                        'style'      => '.test {color: red}',
                        'properties' => 'not valid data'
                    ],
                    'fallback' => null,
                    'string'   => null,
                    'text'     => 'text'
                ]
            ]
        ];
        $response = $this->post(['entity' => 'localizedfallbackvalues'], $data, [], false);

        $this->assertResponseValidationError(
            [
                'title'  => 'form constraint',
                'detail' => 'This value is not valid.',
                'source' => ['pointer' => '/data/attributes/wysiwyg/properties']
            ],
            $response
        );
    }

    public function testUpdateWysiwygField(): void
    {
        $this->enableTwig();

        $data = [
            'data' => [
                'type'       => 'localizedfallbackvalues',
                'id'         => '<toString(@test_value_with_wysiwyg->id)>',
                'attributes' => [
                    'wysiwyg' => [
                        'value'      => 'New Content. Twig Expr: "{{ " test "|trim }}".',
                        'style'      => '.new {color: {{ " blue "|trim }}}',
                        'properties' => ['new_param' => 'new_value']
                    ]
                ]
            ]
        ];
        $response = $this->patch(
            ['entity' => 'localizedfallbackvalues', 'id' => '<toString(@test_value_with_wysiwyg->id)>'],
            $data
        );

        $this->assertResponseContains($data, $response);
    }

    public function testUpdateWysiwygFieldAndRenderedWysiwygFieldThatShouldBeIgnored(): void
    {
        $this->enableTwig();

        $data = [
            'data' => [
                'type'       => 'localizedfallbackvalues',
                'id'         => '<toString(@test_value_with_wysiwyg->id)>',
                'attributes' => [
                    'wysiwyg' => [
                        'value'         => 'New Content. Twig Expr: "{{ " test "|trim }}".',
                        'style'         => '.new {color: {{ " blue "|trim }}}',
                        'properties'    => ['new_param' => 'new_value'],
                        'valueRendered' => '<style type="text/css">.new {color: blue}</style>'
                            . 'New Content. Twig Expr: "test".'
                    ]
                ]
            ]
        ];
        $response = $this->patch(
            ['entity' => 'localizedfallbackvalues', 'id' => '<toString(@test_value_with_wysiwyg->id)>'],
            $data
        );

        $expectedData = $data;
        $expectedData['data']['attributes']['wysiwyg']['valueRendered'] =
            '<style type="text/css">.new {color: blue}</style>New Content. Twig Expr: "test".';
        $this->assertResponseContains($expectedData, $response);
    }

    public function testUpdateWysiwygFieldWithNullValue(): void
    {
        $this->enableTwig();

        $data = [
            'data' => [
                'type'       => 'localizedfallbackvalues',
                'id'         => '<toString(@test_value_with_wysiwyg->id)>',
                'attributes' => [
                    'wysiwyg' => null
                ]
            ]
        ];
        $response = $this->patch(
            ['entity' => 'localizedfallbackvalues', 'id' => '<toString(@test_value_with_wysiwyg->id)>'],
            $data
        );

        $this->assertResponseContains($data, $response);
    }

    public function testUpdateWysiwygFieldWithEmptyValue(): void
    {
        $this->enableTwig();

        $data = [
            'data' => [
                'type'       => 'localizedfallbackvalues',
                'id'         => '<toString(@test_value_with_wysiwyg->id)>',
                'attributes' => [
                    'wysiwyg' => []
                ]
            ]
        ];
        $response = $this->patch(
            ['entity' => 'localizedfallbackvalues', 'id' => '<toString(@test_value_with_wysiwyg->id)>'],
            $data
        );

        $expectedData = $data;
        $expectedData['data']['attributes']['wysiwyg'] = [
            'value'         => 'Content. Twig Expr: "{{ " test "|trim }}".',
            'style'         => '.test {color: {{ " red "|trim }}}',
            'properties'    => ['param' => 'value'],
            'valueRendered' => '<style type="text/css">.test {color: red}</style>'
                . 'Content. Twig Expr: "test".'
        ];
        $this->assertResponseContains($expectedData, $response);
    }

    public function testUpdateWysiwygFieldWithEmptyValuesForProperties(): void
    {
        $this->enableTwig();

        $id = $this->getReference('test_value_with_wysiwyg')->getId();
        $data = [
            'data' => [
                'type'       => 'localizedfallbackvalues',
                'id'         => (string)$id,
                'attributes' => [
                    'wysiwyg' => [
                        'value'      => '',
                        'style'      => '',
                        'properties' => []
                    ]
                ]
            ]
        ];
        $response = $this->patch(
            ['entity' => 'localizedfallbackvalues', 'id' => (string)$id],
            $data
        );

        $expectedData = $data;
        $expectedData['data']['attributes']['wysiwyg'] = null;
        $this->assertResponseContains($expectedData, $response);

        /** @var LocalizedFallbackValue $entity */
        $entity = $this->getEntityManager()->find(LocalizedFallbackValue::class, $id);
        self::assertNull($entity->getWysiwyg());
        self::assertNull($entity->getWysiwygStyle());
        self::assertNull($entity->getWysiwygProperties());
    }

    public function testUpdateWysiwygFieldWithNotAllProperties(): void
    {
        $this->enableTwig();

        $data = [
            'data' => [
                'type'       => 'localizedfallbackvalues',
                'id'         => '<toString(@test_value_with_wysiwyg->id)>',
                'attributes' => [
                    'wysiwyg' => [
                        'value' => 'New Content'
                    ]
                ]
            ]
        ];
        $response = $this->patch(
            ['entity' => 'localizedfallbackvalues', 'id' => '<toString(@test_value_with_wysiwyg->id)>'],
            $data
        );

        $expectedData = $data;
        $expectedData['data']['attributes']['wysiwyg']['style'] = '.test {color: {{ " red "|trim }}}';
        $expectedData['data']['attributes']['wysiwyg']['properties'] = ['param' => 'value'];
        $this->assertResponseContains($expectedData, $response);
    }

    public function testUpdateWysiwygFieldWithInvalidValue(): void
    {
        $this->enableTwig();

        $data = [
            'data' => [
                'type'       => 'localizedfallbackvalues',
                'id'         => '<toString(@test_value_with_wysiwyg->id)>',
                'attributes' => [
                    'wysiwyg' => [
                        'value'      => 'New Content',
                        'style'      => 'new style',
                        'properties' => 'not valid data'
                    ]
                ]
            ]
        ];
        $response = $this->patch(
            ['entity' => 'localizedfallbackvalues', 'id' => '<toString(@test_value_with_wysiwyg->id)>'],
            $data,
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title'  => 'form constraint',
                'detail' => 'This value is not valid.',
                'source' => ['pointer' => '/data/attributes/wysiwyg/properties']
            ],
            $response
        );
    }
}
