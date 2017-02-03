<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\ImportExport;

use Symfony\Component\DomCrawler\Form;
use Symfony\Component\Yaml\Yaml;

use Oro\Bundle\ImportExportBundle\Async\ExportMessageProcessor;
use Oro\Bundle\ImportExportBundle\Job\JobExecutor;
use Oro\Bundle\ImportExportBundle\Processor\ProcessorRegistry;
use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageQueueExtension;
use Oro\Bundle\NotificationBundle\Async\Topics;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\SecurityBundle\Authentication\Token\OrganizationToken;
use Oro\Component\MessageQueue\Transport\Null\NullMessage;
use Oro\Component\MessageQueue\Transport\SessionInterface;

trait sadfh
{
    use MessageQueueExtension;

    /**
     * @return string
     */
    protected function processExportMessage()
    {
        $sentMessages = $this->getSentMessages();
        $exportMessageData = reset($sentMessages);
        $this->tearDownMessageCollector();

        $message = new NullMessage();
        $message->setMessageId('abc');
        $message->setBody(json_encode($exportMessageData['message']));

        /** @var ExportMessageProcessor $processor */
        $processor = $this->getContainer()->get('oro_importexport.async.export');
        $processorResult = $processor->process($message, $this->createSessionInterfaceMock());

        $this->assertEquals(ExportMessageProcessor::ACK, $processorResult);

        $sentMessages = $this->getSentMessages();
        foreach ($sentMessages as $messageData) {
            if (Topics::SEND_NOTIFICATION_EMAIL === $messageData['topic']) {
                break;
            }
        }

        preg_match('/http.*\.csv/', $messageData['message']['body'], $match);
        $urlChunks = explode('/', $match[0]);
        $filename = end($urlChunks);

        $this->client->request(
            'GET',
            $this->getUrl('oro_importexport_export_download', ['fileName' => $filename]),
            [],
            [],
            $this->generateNoHashNavigationHeader()
        );

        $result = $this->client->getResponse();

        $this->assertResponseStatusCodeEquals($result, 200);
        $this->assertResponseContentTypeEquals($result, 'text/csv');
        $this->assertStringStartsWith(
            'attachment; filename="oro_product_product_',
            $result->headers->get('Content-Disposition')
        );

        return $result->getFile()->getPathname();
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|SessionInterface
     */
    private function createSessionInterfaceMock()
    {
        return $this->getMockBuilder(SessionInterface::class)->getMock();
    }
}
