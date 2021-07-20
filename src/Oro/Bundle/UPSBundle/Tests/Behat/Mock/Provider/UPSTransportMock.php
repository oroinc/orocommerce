<?php

namespace Oro\Bundle\UPSBundle\Tests\Behat\Mock\Provider;

use Oro\Bundle\IntegrationBundle\Entity\Transport;
use Oro\Bundle\UPSBundle\Model\PriceRequest;
use Oro\Bundle\UPSBundle\Model\PriceResponse;
use Oro\Bundle\UPSBundle\Provider\UPSTransport;
use Oro\Bundle\UPSBundle\Tests\Behat\Context\FeatureContext;
use Symfony\Component\Yaml\Parser;

class UPSTransportMock extends UPSTransport
{
    /**
     * @var string
     */
    private $cacheDir;

    public function setCacheDir(string $cacheDir)
    {
        $this->cacheDir = $cacheDir;
    }

    /**
     * @param PriceRequest $priceRequest
     * @param Transport $transportEntity
     * @throws \InvalidArgumentException
     * @return PriceResponse
     */
    public function getPriceResponse(PriceRequest $priceRequest, Transport $transportEntity)
    {
        $fileName = FeatureContext::getBehatYamlFilename($this->cacheDir);
        $yamlParser = new Parser();
        $yamlContent = file_get_contents($fileName);

        $data = [
            'RateResponse' => [
                'RatedShipment' => $yamlParser->parse($yamlContent)
            ]
        ];

        return (new PriceResponse())->parse($data);
    }
}
