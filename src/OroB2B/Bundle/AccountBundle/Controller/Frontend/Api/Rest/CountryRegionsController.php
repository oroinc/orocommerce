<?php

namespace OroB2B\Bundle\AccountBundle\Controller\Frontend\Api\Rest;

use FOS\RestBundle\Controller\Annotations\NamePrefix;
use FOS\RestBundle\Controller\Annotations\RouteResource;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;

use Symfony\Component\HttpFoundation\Response;

use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Controller\Api\Rest\CountryRegionsController as BaseController;

/**
 * @RouteResource("country/regions")
 * @NamePrefix("orob2b_api_frontend_country_")
 */
class CountryRegionsController extends BaseController
{
    /**
     * REST GET regions by country
     *
     * @param Country $country
     *
     * @ApiDoc(
     *      description="Get regions by country id",
     *      resource=true
     * )
     * @return Response
     */
    public function getAction(Country $country = null)
    {
        return parent::getAction($country);
    }
}
