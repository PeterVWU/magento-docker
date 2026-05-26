<?php
/**
 * Authorize.net Payment Module.
 *
 * @category  Payment Integration
 * @package   Rootways_Authorizecim
 * @author    Developer RootwaysInc <developer@rootways.com>
 * @copyright 2023 Rootways Inc. (https://www.rootways.com)
 * @license   Rootways Custom License
 * @link      https://www.rootways.com/pub/media/extension_doc/license_agreement.pdf
 */
namespace Rootways\Authorizecim\Gateway\Validator;

use Rootways\Authorizecim\Gateway\Validator\AbstractResponseValidator;
use Magento\Payment\Gateway\Helper\SubjectReader;

/**
 * Class OrderOnlyResponseValidator
 */
class OrderOnlyResponseValidator extends AbstractResponseValidator
{
    public function validate(array $validationSubject)
    {
        $response = SubjectReader::readResponse($validationSubject);
        $errorMessages = [];
        $errorCode = [];
        $validationResult = $this->validateResponse($response);

        if (!$validationResult) {
            if (isset($response['transactionResponse']['errors']['error']['errorText'])) {
                $errorCode[] = $response['transactionResponse']['errors']['error']['errorCode'];
                $errorMessages[] = __($response['transactionResponse']['errors']['error']['errorText']);
            } else {
                if (!empty($response['messages']['resultCode']) &&
                   $response['messages']['resultCode'] != 'Ok' &&
                    !empty($response['messages']['message']['text'])
                   ) {
                    $errorCode[] = $response['messages']['message']['code'];
                    $errorMessages[] = __($response['messages']['message']['text']);
                }
            }
        }

        return $this->createResult($validationResult, $errorMessages, $errorCode);
    }

    protected function validateResponse($response)
    {
        return $this->validationDirectResponse($response) && $this->validatePaymentProfileId($response);
    }
}
