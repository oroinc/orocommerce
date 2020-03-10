<?php

namespace Oro\Bundle\CMSBundle\Tests\Functional\Api\RestJsonApi;

use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Symfony\Component\HttpFoundation\Response;

class WysiwygFieldsTest extends RestJsonApiTestCase
{
    public function testCreateEntityWithInvalidWYSIWYGFields(): void
    {
        $response = $this->post(
            ['entity' => 'localizedfallbackvalues'],
            $this->getLocalizationFallbackData('not valid data'),
            [],
            false
        );

        $this->assertResponseStatusCodeEquals($response, Response::HTTP_BAD_REQUEST);
        $this->assertResponseContains(
            [
                'errors' => [
                    [
                        'status' => (string) Response::HTTP_BAD_REQUEST,
                        'title' => 'form constraint',
                        'detail' => 'This value is not valid. Source: wysiwyg_properties.'
                    ]
                ]
            ],
            $response
        );
    }

    public function testCreateEntityWithValidWYSIWYGFields(): void
    {
        $response = $this->post(
            ['entity' => 'localizedfallbackvalues'],
            $this->getLocalizationFallbackData(['param' => 'value'])
        );

        $this->assertResponseContains($this->getLocalizationFallbackData(['param' => 'value']), $response);
    }

    /**
     * @param mixed $properties
     *
     * @return array
     */
    private function getLocalizationFallbackData($properties): array
    {
        return [
            'data' => [
                'type' => 'localizedfallbackvalues',
                'attributes' => [
                    'wysiwyg' => [
                        'value' => 'Content',
                        'style' => '<style></style>',
                        'properties' => $properties,
                    ],
                    'fallback' => null,
                    'string' => null,
                    'text' => 'text'
                ],
                'relationships' => [
                    'localization' => ['data' => null]
                ]
            ]
        ];
    }
}
