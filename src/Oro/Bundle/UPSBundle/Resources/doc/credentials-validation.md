# UPS API Credentials Validation

The "Check UPS Connection" button on the UPS integration page checks the credentials and ensures that they are entered correctly. Once clicked, a user gets a flash message with the checking results.

### Main Principle

Is the simple Rate request enough to check the UPS API credentials?

The general Rate request requires addresses and packages, but there are none of them on the integration page. However, for the address we use a country from the integration settings and a static postal code which matches all countries. The country and the postal code are enough for the UPS address. 
As for the packages, we use one package with the unit from the integration settings and a simple package type. The package should not weigh much. Therefore, it's valid for the simple request.

For more details, refer to `\Oro\Bundle\UPSBundle\Connection\Validator\Request\Factory\RateUpsConnectionValidatorRequestFactory`.

### What's happening under the hood?
 
Once you click the button, all form data is sent to the server via AJAX. The channel entity received from the request is passed to `Oro\Bundle\UPSBundle\Connection\Validator\UpsConnectionValidatorInterface::validateConnectionByUpsSettings`. Based on this method, the (`Oro\Bundle\IntegrationBundle\Provider\Rest\Client\RestClientInterface`) client and the (`Oro\Bundle\UPSBundle\Client\Request\UpsClientRequestInterface`) request are created by their factories. The request is sent to UPS API via the client. The next step is to check the response. 

If the request fails due to some connection issues, the (`Oro\Bundle\UPSBundle\Connection\Validator\Result\Factory\UpsConnectionValidatorResultFactory::createExceptionResult`) exception validation result is created with the error flash message displayed to a user.

For the request to succeed, the `Oro\Bundle\UPSBundle\Connection\Validator\Result\Factory\UpsConnectionValidatorResultFactory::createResultByUpsClientResponse` method parses the UPS response for any errors. If no errors found, the user gets a flash message with successful results. 

If there are some errors related to authentication, the user gets an error message that reports the UPS connection failure. If there are no errors related to "Unavailable service between locations" or authentication, the user gets a flash message with successful results. Any other errors not related to authentication are displayed as warning flash messages.
