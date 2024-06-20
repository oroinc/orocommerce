<?php

namespace Oro\Bundle\UPSBundle\Tests\Behat\Mock\Provider;

use InvalidArgumentException;
use Oro\Bundle\IntegrationBundle\Entity\Transport;
use Oro\Bundle\UPSBundle\Model\PriceRequest;
use Oro\Bundle\UPSBundle\Model\PriceResponse;
use Oro\Bundle\UPSBundle\Provider\UPSTransport;
use Oro\Bundle\UPSBundle\Tests\Behat\Context\FeatureContext;
use Symfony\Component\Yaml\Parser;

class UPSTransportMock extends UPSTransport
{
    private string $cacheDir;

    public function setCacheDir(string $cacheDir): void
    {
        $this->cacheDir = $cacheDir;
    }

    /**
     * @param PriceRequest $priceRequest
     * @param Transport $transportEntity
     * @throws InvalidArgumentException
     *
     * @return PriceResponse|null
     */
    public function getPriceResponse(PriceRequest $priceRequest, Transport $transportEntity): ?PriceResponse
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
