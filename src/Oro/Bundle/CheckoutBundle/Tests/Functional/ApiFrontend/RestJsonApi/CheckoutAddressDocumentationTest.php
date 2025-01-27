<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Functional\ApiFrontend\RestJsonApi;

use Oro\Bundle\ApiBundle\Request\ApiAction;
use Oro\Bundle\ApiBundle\Tests\Functional\DocumentationTestTrait;
use Oro\Bundle\FrontendBundle\Tests\Functional\ApiFrontend\FrontendRestJsonApiTestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * @group regression
 */
class CheckoutAddressDocumentationTest extends FrontendRestJsonApiTestCase
{
    use DocumentationTestTrait;

    /** @var string used in DocumentationTestTrait */
    private const VIEW = 'frontend_rest_json_api';

    public function testCreateStatusCodes()
    {
        $this->warmUpDocumentationCache();
        $docs = $this->getEntityDocsForAction('checkoutaddresses', ApiAction::CREATE);

        $resourceData = $this->getResourceData($this->getSimpleFormatter()->format($docs));
        self::assertEquals(
            [
                Response::HTTP_FORBIDDEN => ['Returned always']
            ],
            $resourceData['statusCodes']
        );
    }
}
