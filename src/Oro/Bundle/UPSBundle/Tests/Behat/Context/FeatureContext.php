<?php

namespace Oro\Bundle\UPSBundle\Tests\Behat\Context;

use Behat\Gherkin\Node\TableNode;
use Behat\Symfony2Extension\Context\KernelAwareContext;
use Behat\Symfony2Extension\Context\KernelDictionary;
use Oro\Bundle\TestFrameworkBundle\Behat\Context\OroFeatureContext;
use Oro\Bundle\UPSBundle\Entity\ShippingService;
use Symfony\Component\Yaml\Dumper;

class FeatureContext extends OroFeatureContext implements KernelAwareContext
{
    const BEHAT_YAML_DIR_NAME = 'behat';
    const BEHAT_YAML_FILE_NAME = 'ups_shipping_costs_data.yml';
    const HEADER_METHOD = 'Method';
    const HEADER_CURRENCY = 'Currency';
    const HEADER_COST = 'Cost';

    use KernelDictionary;

    /**
     * Configures UPS with expected costs.
     *
     * Example: I expect the following shipping costs:
     *      | Method Code | Currency | Cost |
     *      | 02          | USD      | 99.0 |
     *      | 12          | USD      | 77.0 |
     *
     * @Then /^(?:|I )expect the following shipping costs:$/
     */
    public function iExpectUPSShippingCosts(TableNode $table)
    {
        $data = $this->parseShippingCostsTable($table);

        $yamlDumper = new Dumper();
        $this->writeFile($yamlDumper->dump($data));
    }

    public static function getBehatYamlFilename(string $cacheDir): string
    {
        $behatCacheDir = $cacheDir . DIRECTORY_SEPARATOR . self::BEHAT_YAML_DIR_NAME;

        if (!file_exists($behatCacheDir)) {
            mkdir($behatCacheDir);
        }

        return $behatCacheDir . DIRECTORY_SEPARATOR . self::BEHAT_YAML_FILE_NAME;
    }

    private function parseShippingCostsTable(TableNode $table): array
    {
        $rows = $table->getRows();
        $header = array_shift($rows);

        $absentHeaders = array_diff([self::HEADER_METHOD, self::HEADER_CURRENCY, self::HEADER_COST], $header);

        if (!empty($absentHeaders)) {
            throw new \InvalidArgumentException(
                sprintf('Some required headers are absent: %s', implode(',', $absentHeaders))
            );
        }

        $columnsByHeaders = array_flip($header);

        $data = [];
        foreach ($rows as $row) {
            $data[] = [
                'Service' => [
                    'Code' => $this->getMethodCodeByDescription($row[$columnsByHeaders[self::HEADER_METHOD]]),
                    'Description' => $row[$columnsByHeaders[self::HEADER_METHOD]],
                ],
                'TotalCharges' => [
                    'CurrencyCode' => $row[$columnsByHeaders[self::HEADER_CURRENCY]],
                    'MonetaryValue' => $row[$columnsByHeaders[self::HEADER_COST]],
                ]
            ];
        }

        return $data;
    }

    private function getMethodCodeByDescription(string $description): string
    {
        $entityManager = $this->getContainer()->get('doctrine')->getManagerForClass(ShippingService::class);
        $repository = $entityManager->getRepository(ShippingService::class);

        /** @var ShippingService $shippingService */
        $shippingService =  $repository->findOneBy(['description' => $description]);

        if (!$shippingService) {
            throw new \InvalidArgumentException(sprintf('No shipping service "%s" found', $description));
        }

        return $shippingService->getCode();
    }

    private function writeFile(string $content)
    {
        $cacheDir = $this->getContainer()->getParameter('kernel.cache_dir');
        $yamlFile = fopen(self::getBehatYamlFilename($cacheDir), 'wt');
        fwrite($yamlFile, $content);
        fclose($yamlFile);
    }
}
