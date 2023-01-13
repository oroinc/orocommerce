<?php

namespace Oro\Bundle\CMSBundle\Tests\Functional\Controller;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\CMSBundle\Entity\Page;
use Oro\Bundle\DigitalAssetBundle\Tests\Functional\DataFixtures\LoadDigitalAssetData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class WysiwygContentControllerTest extends WebTestCase
{
    private const CONTENT = <<<HTML
<div data-title="home-page-slider" data-type="image_slider" class="content-widget content-placeholder">
    {{ widget("home-page-slider") }}
</div>
<div id="i7nk">
  <img id="io4r7" src="%s" alt="source.jpg"/>
</div>
<style>
</style>
HTML;

    protected function setUp(): void
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->loadFixtures([
            LoadDigitalAssetData::class,
        ]);
    }

    /**
     * @dataProvider getValidateActionDataProvider
     */
    public function testValidateAction(?string $content): void
    {
        $response = $this->doValidateRequest($content, Page::class, 'content');

        $this->assertJsonResponseStatusCodeEquals($response, 200);
        $this->assertEquals(['success' => true, 'errors' => []], \json_decode($response->getContent(), true));
    }

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

        $response = $this->doValidateRequest($content, Page::class, 'content');

        $this->assertJsonResponseStatusCodeEquals($response, 200);
        $this->assertEquals(
            [
                'success' => false,
                'errors' => [
                    [
                        'message' => 'Line #14: Unrecognized "<style>" meta tag and all descendants should be removed',
                        'line' => 14,
                        'messageRaw' => 'Unrecognized "<style>" meta tag and all descendants should be removed',
                    ],
                    [
                        'message' => 'Line #15: Unrecognized "</style>" meta tag and all descendants should be removed',
                        'line' => 15,
                        'messageRaw' => 'Unrecognized "</style>" meta tag and all descendants should be removed',
                    ],
                    [
                        'message' => 'Line #7: "src" attribute on "<iframe>" should be removed',
                        'line' => 7,
                        'messageRaw' => '"src" attribute on "<iframe>" should be removed',
                    ],
                    [
                        'message' => 'Line #11: "href" attribute on "<a>" should be removed',
                        'line' => 11,
                        'messageRaw' => '"href" attribute on "<a>" should be removed',
                    ],
                ],
            ],
            \json_decode($response->getContent(), true)
        );
    }

    public function testValidateActionClassNameException(): void
    {
        $response = $this->doValidateRequest('<b>Test</b>', null, 'content');

        $this->assertResponseStatusCodeEquals($response, 400, 'ClassName field is required.');
    }

    public function testValidateActionFieldNameException(): void
    {
        $response = $this->doValidateRequest('<b>Test</b>', Page::class, null);

        $this->assertResponseStatusCodeEquals($response, 400, 'FieldName field is required.');
    }

    public function testResolveAction(): void
    {
        /** @var File $file */
        $file = $this->getReference(LoadDigitalAssetData::DIGITAL_ASSET_1_CHILD_2);

        $response = $this->doResolveRequest(
            sprintf(
                self::CONTENT,
                sprintf("{{ wysiwyg_image('%s','%s') }}", $file->getId(), $file->getUuid())
            )
        );

        $expected = sprintf(
            self::CONTENT,
            sprintf(
                "/media/cache/attachment/filter/wysiwyg_original/8e0a8a5b130f0949069e67b73a953ac1/%s/source.file",
                $file->getId()
            )
        );

        $this->assertJsonResponseStatusCodeEquals($response, 200);
        $this->assertEquals(['success' => true, 'content' => $expected], \json_decode($response->getContent(), true));
    }

    public function testResolveActionNoContent(): void
    {
        $response = $this->doResolveRequest(null);

        $this->assertResponseStatusCodeEquals($response, 200);
        $this->assertEquals(['success' => true, 'content' => ''], \json_decode($response->getContent(), true));
    }

    public function testResolveActionErrorContent(): void
    {
        $response = $this->doResolveRequest("{{ wysiwyg_image(111, 111) }}");

        $this->assertResponseStatusCodeEquals($response, 400);
        $this->assertEquals(
            ['success' => false, 'content' => "{{ wysiwyg_image(111, 111) }}"],
            \json_decode($response->getContent(), true)
        );
    }

    private function doValidateRequest(?string $content, ?string $className, ?string $fieldName): Response
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
            $this->getUrl('oro_cms_wysiwyg_content_validate'),
            $params,
            [],
            ['HTTP_X-Requested-With' => 'XMLHttpRequest']
        );

        return $this->client->getResponse();
    }

    private function doResolveRequest(?string $content): Response
    {
        $params = [];
        if ($content) {
            $params['content'] = $content;
        }

        $this->client->request('POST', $this->getUrl('oro_cms_wysiwyg_content_resolve'), $params);

        return $this->client->getResponse();
    }
}
