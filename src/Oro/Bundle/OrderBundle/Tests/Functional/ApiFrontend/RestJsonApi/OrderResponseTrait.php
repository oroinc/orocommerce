<?php

namespace Oro\Bundle\OrderBundle\Tests\Functional\ApiFrontend\RestJsonApi;

use Symfony\Component\HttpFoundation\Response;

trait OrderResponseTrait
{
    protected function updateOrderResponseContent(array|string $expectedContent, Response $response): array
    {
        $responseContent = $this->updateResponseContent($expectedContent, $response);

        return $this->updateResponseContent($responseContent, $response, 'identifier');
    }
}
