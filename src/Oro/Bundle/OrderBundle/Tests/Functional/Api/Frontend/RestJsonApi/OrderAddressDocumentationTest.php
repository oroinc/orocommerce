<?php

namespace Oro\Bundle\OrderBundle\Tests\Functional\Api\Frontend\RestJsonApi;

use Oro\Bundle\ApiBundle\Request\ApiAction;
use Oro\Bundle\ApiBundle\Tests\Functional\DocumentationTestTrait;
use Oro\Bundle\FrontendBundle\Tests\Functional\Api\FrontendRestJsonApiTestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * @group regression
 */
class OrderAddressDocumentationTest extends FrontendRestJsonApiTestCase
{
    use DocumentationTestTrait;

    /** @var string used in DocumentationTestTrait */
    private const VIEW = 'frontend_rest_json_api';

    public function testCreateStatusCodes()
    {
        $this->warmUpDocumentationCache();
        $docs = $this->getEntityDocsForAction('orderaddresses', ApiAction::CREATE);

        $data = $this->getSimpleFormatter()->format($docs);
        $resourceData = reset($data);
        $resourceData = reset($resourceData);
        self::assertEquals(
            [
                Response::HTTP_FORBIDDEN => ['Returned always']
            ],
            $resourceData['statusCodes']
        );
    }
}
