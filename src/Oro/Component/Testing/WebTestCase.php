<?php

namespace OroB2B\src\Oro\Component\Testing;

use Symfony\Component\HttpFoundation\Response;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase as OroWebTestCase;

/**
 * @inheritdoc
 */
abstract class WebTestCase extends OroWebTestCase
{
    /**
     * @param array|string $gridParameters
     * @param array        $filter
     *
     * @return Response
     */
    public function requestFrontendGrid($gridParameters, $filter = [])
    {
        if (is_string($gridParameters)) {
            $gridParameters = ['gridName' => $gridParameters];
        }

        //transform parameters to nested array
        $parameters = [];
        foreach ($filter as $param => $value) {
            $param .= '=' . $value;
            parse_str($param, $output);
            $parameters = array_merge_recursive($parameters, $output);
        }

        $gridParameters = array_merge_recursive($gridParameters, $parameters);

        $this->client->request(
            'GET',
            $this->getUrl('orob2b_customer_frontend_account_user_datagrid_index', $gridParameters)
        );

        return $this->client->getResponse();
    }
}
