<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\ImportExport;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Oro\Bundle\ImportExportBundle\Job\JobExecutor;
use Oro\Bundle\ImportExportBundle\Processor\ProcessorRegistry;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\SecurityBundle\Authentication\Token\OrganizationToken;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Component\Testing\ReflectionUtil;

abstract class AbstractImportExportTestCase extends WebTestCase
{
    protected function setUp(): void
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->client->useHashNavigation(true);
    }

    protected function validateImportFile(string $strategy, string $file): void
    {
        $crawler = $this->client->request(
            'GET',
            $this->getUrl('oro_importexport_import_form', ['entity' => Product::class, '_widgetContainer' => 'dialog'])
        );
        $result = $this->client->getResponse();
        self::assertHtmlResponseStatusCodeEquals($result, 200);

        self::assertFileExists($file);

        $form = $crawler->selectButton('Submit')->form();

        /** Change after BAP-1813 */
        $form->getFormNode()->setAttribute(
            'action',
            $form->getFormNode()->getAttribute('action') . '&_widgetContainer=dialog'
        );

        $form['oro_importexport_import[file]']->upload($file);
        $form['oro_importexport_import[processorAlias]'] = $strategy;

        $this->client->followRedirects();
        $this->client->submit($form);

        $result = $this->client->getResponse();

        self::assertHtmlResponseStatusCodeEquals($result, 200);

        $crawler = $this->client->getCrawler();
        self::assertEquals(0, $crawler->filter('.import-errors')->count());
    }

    protected function doExport(): void
    {
        $this->client->followRedirects(false);
        $this->client->request(
            'GET',
            $this->getUrl(
                'oro_importexport_export_instant',
                ['processorAlias' => 'oro_product_product', '_format' => 'json']
            )
        );

        $data = self::getJsonResponseContent($this->client->getResponse(), 200);

        self::assertTrue($data['success']);
        self::assertEquals(1, $data['readsCount']);
        self::assertEquals(0, $data['errorsCount']);

        $this->client->request(
            'GET',
            $data['url'],
            [],
            [],
            self::generateNoHashNavigationHeader()
        );

        $result = $this->client->getResponse();
        self::assertResponseStatusCodeEquals($result, 200);
        self::assertResponseContentTypeEquals($result, 'text/csv');
    }

    protected function validateExportResultWithImportTemplate(string $importTemplateFile): void
    {
        $importTemplate = $this->getFileContents($importTemplateFile);
        $exportedData = $this->getFileContents($this->getExportFile());

        $commonFields = array_intersect($importTemplate[0], $exportedData[0]);

        $importTemplateValues = $this->extractFieldValues($commonFields, $importTemplate);
        $exportedDataValues = $this->extractFieldValues($commonFields, $exportedData);

        self::assertEquals($importTemplateValues, $exportedDataValues);
    }

    protected function extractFieldValues(array $fields, array $data): array
    {
        $values = [];
        foreach ($fields as $field) {
            $key = array_search($field, $data[0], true);
            if (false !== $key) {
                $values[$field] = $data[1][$key];
            }
        }

        return $values;
    }

    protected function doImport(string $strategy): array
    {
        $this->client->followRedirects(false);
        $this->client->request(
            'GET',
            $this->getUrl('oro_importexport_import_process', ['processorAlias' => $strategy, '_format' => 'json'])
        );

        return self::getJsonResponseContent($this->client->getResponse(), 200);
    }

    protected function getImportTemplate(): string
    {
        $result = self::getContainer()->get('oro_importexport.handler.export')
            ->getExportResult(
                JobExecutor::JOB_EXPORT_TEMPLATE_TO_CSV,
                'oro_product_product_export_template',
                ProcessorRegistry::TYPE_EXPORT_TEMPLATE
            );

        return self::getContainer()->get('oro_importexport.file.file_manager')
            ->writeToTmpLocalStorage($result['file']);
    }

    protected function getExportFile(): string
    {
        $result = self::getContainer()->get('oro_importexport.handler.export')
            ->handleExport(JobExecutor::JOB_EXPORT_TO_CSV, 'oro_product_product');

        self::assertResponseStatusCodeEquals($result, 200);
        self::assertResponseContentTypeEquals($result, 'application/json');

        $result = json_decode($result->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertTrue($result['success']);
        self::assertEquals(0, $result['errorsCount']);

        return self::getContainer()->get('oro_importexport.file.file_manager')
            ->writeToTmpLocalStorage($result['file']);
    }

    protected function getFileContents(string $fileName): array
    {
        $content = file_get_contents($fileName);
        $content = explode("\n", $content);
        $content = array_filter($content, 'strlen');

        return array_map('str_getcsv', $content);
    }

    protected function validateExportResult(string $exportFile, int $expectedItemsCount): void
    {
        $exportedData = $this->getFileContents($exportFile);
        unset($exportedData[0]);

        self::assertCount($expectedItemsCount, $exportedData);
    }

    protected function cleanUpReader(): void
    {
        $reader = self::getContainer()->get('oro_importexport.reader.csv');
        ReflectionUtil::setPropertyValue($reader, 'file', null);
        ReflectionUtil::setPropertyValue($reader, 'header', null);
    }

    protected function assertImportResponse(array $data, int $added, int $updated): void
    {
        self::assertEquals(
            [
                'success'    => true,
                'message'    => 'File was successfully imported.',
                'errorsUrl'  => null,
                'importInfo' => $added . ' products were added, ' . $updated . ' products were updated',
            ],
            $data
        );
    }

    protected function setSecurityToken(): void
    {
        $token = new OrganizationToken($this->getRepository(Organization::class)->findOneBy([]));
        $token->setUser($this->getRepository(User::class)->findOneBy([]));
        self::getContainer()->get('security.token_storage')->setToken($token);
    }

    protected function getManager(string $class): EntityManagerInterface
    {
        return self::getContainer()->get('doctrine')->getManagerForClass($class);
    }

    protected function getRepository(string $class): EntityRepository
    {
        return $this->getManager($class)->getRepository($class);
    }
}
