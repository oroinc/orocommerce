<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\Controller\Frontend;

use Oro\Bundle\ConfigBundle\Tests\Functional\Traits\ConfigManagerAwareTestTrait;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCustomerUserData;
use Oro\Bundle\FrontendImportExportBundle\Async\Topic\PreExportTopic;
use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageQueueExtension;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\WebsiteSearchBundle\Tests\Functional\Traits\DefaultLocalizationIdTestTrait;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class ExportControllerTest extends WebTestCase
{
    use MessageQueueExtension;
    use ConfigManagerAwareTestTrait;
    use DefaultLocalizationIdTestTrait;

    protected function setUp(): void
    {
        $this->initClient(
            [],
            self::generateBasicAuthHeader(LoadCustomerUserData::EMAIL, LoadCustomerUserData::PASSWORD)
        );

        $this->loadFixtures([LoadCustomerUserData::class]);

        self::getConfigManager()->set('oro_product.product_data_export_enabled', true);
    }

    protected function tearDown(): void
    {
        self::getConfigManager()->set('oro_product.product_data_export_enabled', false);

        parent::tearDown();
    }

    public function testShouldSendPreExportMessageOnExportAction(): void
    {
        $this->client->disableReboot();

        $referer = $this->getUrl('oro_product_frontend_export');
        $this->ajaxRequest(
            'POST',
            $this->getUrl('oro_product_frontend_export'),
            [],
            [],
            ['HTTP_REFERER' => $referer]
        );

        self::assertJsonResponseStatusCodeEquals($this->client->getResponse(), 200);

        self::assertMessageSent(PreExportTopic::getName(), [
            'jobName' => 'filtered_frontend_product_export_to_csv',
            'processorAlias' => 'oro_product_frontend_product_listing',
            'outputFilePrefix' => 'product',
            'refererUrl' => $referer,
            'options' => [
                'filteredResultsGrid' => 'frontend-product-search-grid',
                'currentLocalizationId' => $this->getDefaultLocalizationId(),
                'currentCurrency' => 'USD',
            ],
            'userId' => $this->getReference(LoadCustomerUserData::EMAIL)->getId(),
        ]);
    }

    public function testShouldSendPreExportMessageOnExportActionWithEmptyRefererUrlWhenRefererIsNotSameSite(): void
    {
        $this->client->disableReboot();

        $referer = 'http://example.org';
        $this->ajaxRequest(
            'POST',
            $this->getUrl('oro_product_frontend_export'),
            [],
            [],
            ['HTTP_REFERER' => $referer]
        );

        self::assertJsonResponseStatusCodeEquals($this->client->getResponse(), 200);

        self::assertMessageSent(PreExportTopic::getName(), [
            'jobName' => 'filtered_frontend_product_export_to_csv',
            'processorAlias' => 'oro_product_frontend_product_listing',
            'outputFilePrefix' => 'product',
            'refererUrl' => '',
            'options' => [
                'filteredResultsGrid' => 'frontend-product-search-grid',
                'currentLocalizationId' => $this->getDefaultLocalizationId(),
                'currentCurrency' => 'USD',
            ],
            'userId' => $this->getReference(LoadCustomerUserData::EMAIL)->getId(),
        ]);
    }
}
