<?php

namespace Oro\Bundle\CMSBundle\Tests\Functional\Controller;

use Oro\Bundle\AttachmentBundle\Entity\File;
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

    public function testResolveAction(): void
    {
        /** @var File $file */
        $file = $this->getReference(LoadDigitalAssetData::DIGITAL_ASSET_1_CHILD_2);

        $response = $this->doRequest(
            sprintf(
                self::CONTENT,
                sprintf("{{ wysiwyg_image('%s','%s') }}", $file->getId(), $file->getUuid())
            )
        );

        $expected = sprintf(
            self::CONTENT,
            sprintf(
                "/media/cache/attachment/resize/wysiwyg_original/11c00c6d0bd6b875afe655d3c9d4f942/%s/source.file",
                $file->getId()
            )
        );

        $this->assertJsonResponseStatusCodeEquals($response, 200);
        $this->assertEquals(['success' => true, 'content' => $expected], \json_decode($response->getContent(), true));
    }

    public function testResolveActionNoContent(): void
    {
        $response = $this->doRequest(null);

        $this->assertResponseStatusCodeEquals($response, 200);
        $this->assertEquals(['success' => true, 'content' => ''], \json_decode($response->getContent(), true));
    }

    public function testResolveActionErrorContent(): void
    {
        $response = $this->doRequest("{{ wysiwyg_image(111, 111) }}");

        $this->assertResponseStatusCodeEquals($response, 400);
        $this->assertEquals(
            ['success' => false, 'content' => "{{ wysiwyg_image(111, 111) }}"],
            \json_decode($response->getContent(), true)
        );
    }

    /**
     * @param string|null $content
     * @return Response
     */
    private function doRequest(?string $content): Response
    {
        $params = [];
        if ($content) {
            $params['content'] = $content;
        }

        $this->client->request('POST', $this->getUrl('oro_cms_wysiwyg_content_resolve'), $params);

        return $this->client->getResponse();
    }
}
