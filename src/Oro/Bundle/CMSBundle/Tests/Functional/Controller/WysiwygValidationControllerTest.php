<?php

namespace Oro\Bundle\CMSBundle\Tests\Functional\Controller;

use Oro\Bundle\CMSBundle\Entity\Page;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class WysiwygValidationControllerTest extends WebTestCase
{
    protected function setUp(): void
    {
        $this->initClient([], $this->generateBasicAuthHeader());
    }

    /**
     * @param string|null $content
     * @dataProvider getValidateActionDataProvider
     */
    public function testValidateAction(?string $content): void
    {
        $response = $this->doRequest($content, Page::class, 'content');

        $this->assertJsonResponseStatusCodeEquals($response, 200);
        $this->assertEquals(['success' => true, 'errors' => []], \json_decode($response->getContent(), true));
    }

    /**
     * @return array
     */
    public function getValidateActionDataProvider(): array
    {
        return [
            'null' => [
                'content' => null,
            ],
            'empty string' => [
                'content' => '',
            ],
            'content' => [
                'content' => '<b>Test</b>',
            ],
        ];
    }

    public function testValidateActionError(): void
    {
        $content = <<<HTML
<div data-title="home-page-slider" data-type="image_slider" class="content-widget content-placeholder">
    {{ widget("home-page-slider") }}
</div>
<div id="i7nk">
  <iframe allowfullscreen="allowfullscreen" id="ikah" src="https://www.youtube.com/embed/D733JoYu92k?"></iframe>
  <div id="irn2">
    <iframe src="https://www.w3schools.com"></iframe>
    <div>
      <br/>
    </div>
    <a href="javascript:alert(1)" class="link">test</a>
  </div>
</div>
<style>
</style>
HTML;

        $response = $this->doRequest($content, Page::class, 'content');

        $this->assertJsonResponseStatusCodeEquals($response, 200);
        $this->assertEquals(
            [
                'success' => false,
                'errors' => [
                    [
                        'message' => 'Line #14: Unrecognized <style> meta tag and all descendants should be removed',
                        'line' => 14,
                    ],
                    [
                        'message' => 'Line #15: Unrecognized </style> meta tag and all descendants should be removed',
                        'line' => 15,
                    ],
                    [
                        'message' => 'Line #7: src attribute on <iframe> should be removed',
                        'line' => 7,
                    ],
                    [
                        'message' => 'Line #11: href attribute on <a> should be removed',
                        'line' => 11,
                    ],
                ],
            ],
            \json_decode($response->getContent(), true)
        );
    }

    public function testValidateActionClassNameException(): void
    {
        $response = $this->doRequest('<b>Test</b>', null, 'content');

        $this->assertResponseStatusCodeEquals($response, 400, 'ClassName field is required.');
    }

    public function testValidateActionFieldNameException(): void
    {
        $response = $this->doRequest('<b>Test</b>', Page::class, null);

        $this->assertResponseStatusCodeEquals($response, 400, 'FieldName field is required.');
    }

    /**
     * @param string|null $content
     * @param string|null $className
     * @param string|null $fieldName
     * @return Response
     */
    private function doRequest(?string $content, ?string $className, ?string $fieldName): Response
    {
        $params = [];
        if ($content) {
            $params['content'] = $content;
        }
        if ($className) {
            $params['className'] = $className;
        }
        if ($fieldName) {
            $params['fieldName'] = $fieldName;
        }

        $this->client->request(
            'POST',
            $this->getUrl('oro_cms_wysiwyg_validation_validate'),
            $params,
            [],
            ['HTTP_X-Requested-With' => 'XMLHttpRequest']
        );

        return $this->client->getResponse();
    }
}
