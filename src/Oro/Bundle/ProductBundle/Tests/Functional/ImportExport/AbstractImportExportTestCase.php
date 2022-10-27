<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\ImportExport;

use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Persistence\ObjectRepository;
use Oro\Bundle\ImportExportBundle\Job\JobExecutor;
use Oro\Bundle\ImportExportBundle\Processor\ProcessorRegistry;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\SecurityBundle\Authentication\Token\OrganizationToken;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\Testing\ReflectionUtil;
use Symfony\Component\DomCrawler\Form;

abstract class AbstractImportExportTestCase extends WebTestCase
{
    protected function setUp(): void
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->client->useHashNavigation(true);
    }

    /**
     * @param string $strategy
     * @param string $file
     */
    protected function validateImportFile($strategy, $file)
    {
        $crawler = $this->client->request(
            'GET',
            $this->getUrl(
                'oro_importexport_import_form',
                [
                    'entity' => Product::class,
                    '_widgetContainer' => 'dialog',
                ]
            )
        );
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        $this->assertFileExists($file);

        /** @var Form $form */
        $form = $crawler->selectButton('Submit')->form();

        /** Change after BAP-1813 */
        $form->getFormNode()->setAttribute(
            'action',
            $form->getFormNode()->getAttribute('action') . '&_widgetContainer=dialog'
        );

        $form['oro_importexport_import[file]']->upload($file);
        $form['oro_importexport_import[processorAlias]'] = $strategy;

        $this->client->followRedirects(true);
        $this->client->submit($form);

        $result = $this->client->getResponse();

        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        $crawler = $this->client->getCrawler();
        $this->assertEquals(0, $crawler->filter('.import-errors')->count());
    }

    protected function doExport()
    {
        $this->client->followRedirects(false);
        $this->client->request(
            'GET',
            $this->getUrl(
                'oro_importexport_export_instant',
                [
                    'processorAlias' => 'oro_product_product',
                    '_format' => 'json',
                ]
            )
        );

        $data = $this->getJsonResponseContent($this->client->getResponse(), 200);

        $this->assertTrue($data['success']);
        $this->assertEquals(1, $data['readsCount']);
        $this->assertEquals(0, $data['errorsCount']);

        $this->client->request(
            'GET',
            $data['url'],
            [],
            [],
            $this->generateNoHashNavigationHeader()
        );

        $result = $this->client->getResponse();
        $this->assertResponseStatusCodeEquals($result, 200);
        $this->assertResponseContentTypeEquals($result, 'text/csv');
    }

    /**
     * @param string $importTemplateFile
     */
    protected function validateExportResultWithImportTemplate($importTemplateFile)
    {
        $importTemplate = $this->getFileContents($importTemplateFile);
        $exportedData = $this->getFileContents($this->getExportFile());

        $commonFields = array_intersect($importTemplate[0], $exportedData[0]);

        $importTemplateValues = $this->extractFieldValues($commonFields, $importTemplate);
        $exportedDataValues = $this->extractFieldValues($commonFields, $exportedData);

        $this->assertEquals($importTemplateValues, $exportedDataValues);
    }

    /**
     * @param array $fields
     * @param array $data
     * @return array
     */
    protected function extractFieldValues(array $fields, array $data)
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

    /**
     * @param string $strategy
     * @return array
     */
    protected function doImport($strategy)
    {
        $this->client->followRedirects(false);
        $this->client->request(
            'GET',
            $this->getUrl(
                'oro_importexport_import_process',
                [
                    'processorAlias' => $strategy,
                    '_format' => 'json',
                ]
            )
        );

        return $this->getJsonResponseContent($this->client->getResponse(), 200);
    }

    /**
     * @return string
     */
    protected function getImportTemplate()
    {
        $result = $this
            ->getContainer()
            ->get('oro_importexport.handler.export')
            ->getExportResult(
                JobExecutor::JOB_EXPORT_TEMPLATE_TO_CSV,
                'oro_product_product_export_template',
                ProcessorRegistry::TYPE_EXPORT_TEMPLATE
            );

        return $this
            ->getContainer()
            ->get('oro_importexport.file.file_manager')
            ->writeToTmpLocalStorage($result['file']);
    }

    /**
     * @return string
     */
    protected function getExportFile()
    {
        $result = $this
            ->getContainer()
            ->get('oro_importexport.handler.export')
            ->handleExport(
                JobExecutor::JOB_EXPORT_TO_CSV,
                'oro_product_product',
                ProcessorRegistry::TYPE_EXPORT
            );

        $this->assertResponseStatusCodeEquals($result, 200);
        $this->assertResponseContentTypeEquals($result, 'application/json');

        $result = json_decode($result->getContent(), true);

        $this->assertTrue($result['success']);
        $this->assertEquals(0, $result['errorsCount']);

        return $this
            ->getContainer()
            ->get('oro_importexport.file.file_manager')
            ->writeToTmpLocalStorage($result['file']);
    }

    /**
     * @param string $fileName
     * @return array
     */
    protected function getFileContents($fileName)
    {
        $content = file_get_contents($fileName);
        $content = explode("\n", $content);
        $content = array_filter($content, 'strlen');

        return array_map('str_getcsv', $content);
    }

    /**
     * @param string $exportFile
     * @param int $expectedItemsCount
     */
    protected function validateExportResult($exportFile, $expectedItemsCount)
    {
        $exportedData = $this->getFileContents($exportFile);
        unset($exportedData[0]);

        $this->assertCount($expectedItemsCount, $exportedData);
    }

    protected function cleanUpReader()
    {
        $reader = $this->getContainer()->get('oro_importexport.reader.csv');
        ReflectionUtil::setPropertyValue($reader, 'file', null);
        ReflectionUtil::setPropertyValue($reader, 'header', null);
    }

    /**
     * @param array $data
     * @param int $added
     * @param int $updated
     */
    protected function assertImportResponse(array $data, $added, $updated)
    {
        $this->assertEquals(
            [
                'success'    => true,
                'message'    => 'File was successfully imported.',
                'errorsUrl'  => null,
                'importInfo' => $added . ' products were added, ' . $updated . ' products were updated',
            ],
            $data
        );
    }

    protected function setSecurityToken()
    {
        $token = new OrganizationToken(
            $this->getRepository('OroOrganizationBundle:Organization')->findOneBy([])
        );
        $token->setUser(
            $this->getRepository('OroUserBundle:User')->findOneBy([])
        );
        $this->getContainer()->get('security.token_storage')->setToken($token);
    }

    /**
     * @param string $class
     * @return ObjectManager|null|object
     */
    protected function getManager($class)
    {
        return $this->getContainer()->get('doctrine')->getManagerForClass($class);
    }

    /**
     * @param string $class
     * @return ObjectRepository|EntityRepository
     */
    protected function getRepository($class)
    {
        return $this->getManager($class)->getRepository($class);
    }
}
