<?php

namespace Oro\Bundle\CMSBundle\Tests\Functional\Controller;

use Oro\Bundle\CMSBundle\Entity\ContentTemplate;
use Oro\Bundle\CMSBundle\Tests\Functional\DataFixtures\LoadContentTemplateData;
use Oro\Bundle\CMSBundle\Tools\DigitalAssetTwigTagsConverter;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class ContentTemplateControllerTest extends WebTestCase
{
    private DigitalAssetTwigTagsConverter $digitalAssetTwigTagsConverter;

    protected function setUp(): void
    {
        $this->initClient([], self::generateBasicAuthHeader());
        $this->loadFixtures([
            LoadContentTemplateData::class,
        ]);

        $this->digitalAssetTwigTagsConverter = self::getContainer()
            ->get('oro_cms.tools.digital_asset_twig_tags_converter');
    }

    /**
     * @dataProvider getContentTemplateDataProvider
     */
    public function testGetContentTemplate(string $contentTemplateReferenceName): void
    {
        /** @var ContentTemplate $contentTemplate */
        $contentTemplate = $this->getReference($contentTemplateReferenceName);

        $this->ajaxRequest(
            'GET',
            $this->getUrl('oro_cms_content_template_content', ['id' => $contentTemplate->getId()])
        );

        self::assertSame(
            [
                'content' => $this->digitalAssetTwigTagsConverter
                    ->convertToUrls((string)$contentTemplate->getContent()),
                'contentStyle' => $this->digitalAssetTwigTagsConverter
                    ->convertToUrls((string)$contentTemplate->getContentStyle()),
                'contentProperties' => (array)$contentTemplate->getContentProperties(),
            ],
            self::getJsonResponseContent($this->client->getResponse(), 200)
        );
    }

    public function getContentTemplateDataProvider(): array
    {
        return [
            'empty' => [
                'contentTemplateReferenceName' => LoadContentTemplateData::CONTENT_TEMPLATE_2,
            ],
            'with content' => [
                'contentTemplateReferenceName' => LoadContentTemplateData::CONTENT_TEMPLATE_1,
            ],
        ];
    }
}
