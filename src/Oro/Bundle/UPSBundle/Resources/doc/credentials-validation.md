# UPS API credentials validation

The "Check UPS Connection" button on an UPS integration page provide possibility for checking that the credentials were entered correctly. After clicking user see flash message with result of the checking.

### Main principe

Is simple Rate request to UPS API used for checking credentials.

General Rate request requires addresses and packages, but on integration page we don't have them. First of all is - address, we use country from integration settings and static postal code, which will match better for all countries. Country and postal code are enough for UPS address. Second problem is packages. We use one package with unit from integration settings, simple package type and not big weight. And it's also valid for simple request.

Check `\Oro\Bundle\UPSBundle\Connection\Validator\Request\Factory\RateUpsConnectionValidatorRequestFactory` for details.

### What's happening under the hood?
 
All form data sends to server via AJAX after pressing the button. Channel entity,received from request, is passed into `Oro\Bundle\UPSBundle\Connection\Validator\UpsConnectionValidatorInterface::validateConnectionByUpsSettings`. In this method client (`Oro\Bundle\IntegrationBundle\Provider\Rest\Client\RestClientInterface`) and request (`Oro\Bundle\UPSBundle\Client\Request\UpsClientRequestInterface`) are created by their factories. Request is sent to UPS API via client. Next step is to check the response. 

If request fails by some connection issues, exception result of validation will be created (`Oro\Bundle\UPSBundle\Connection\Validator\Result\Factory\UpsConnectionValidatorResultFactory::createExceptionResult`) and user will see error flash message.

On success request, `Oro\Bundle\UPSBundle\Connection\Validator\Result\Factory\UpsConnectionValidatorResultFactory::createResultByUpsClientResponse` method parse UPS response for any errors. User will see success flash message if there are no errors. 

If there are some errors related to authentication user will see error about unsuccessful UPS connection. In case when response contains no errors related to "Unavailable service between locations" and no authentication errors user also will see success flash message. Any other errors which don't relate to authentication will be shown as warning flash message.
