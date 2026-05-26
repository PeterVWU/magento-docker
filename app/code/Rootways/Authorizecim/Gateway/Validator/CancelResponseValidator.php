<?php
namespace Rootways\Authorizecim\Gateway\Validator;

use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Validator\ResultInterfaceFactory;

/**
 * CancelResponseValidator
 */
class CancelResponseValidator extends AbstractResponseValidator
{
    /**
     * @var \Rootways\Authorizecim\Model\Request\Api
     */
    private $customApi;

    /**
     * @param \Rootways\Authorizecim\Model\Request\Api $customApi
     * @param ResultInterfaceFactory $resultFactory
     */
    public function __construct(
        \Rootways\Authorizecim\Model\Request\Api $customApi,
        ResultInterfaceFactory $resultFactory
    ) {
        $this->customApi = $customApi;
        parent::__construct($resultFactory);
    }

    public function validate(array $validationSubject)
    {
        $response = SubjectReader::readResponse($validationSubject);
        $errorMessages = [];
        $errorCode = [];
        $validationResult = $this->responseCode($response)
            && $this->validateTxrefnum($response);

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

        if (!empty($errorCode) && !empty($response['transactionResponse']['refTransID'])) {
            $getTraData = $this->customApi->getTraDetail($response['transactionResponse']['refTransID']);
            if (!empty($getTraData['transaction']['transactionStatus']) &&
                $getTraData['transaction']['transactionStatus'] == 'expired'
            ) {
                $validationResult = true;
                $errorMessages = [];
                $errorCode = [];
            } elseif (in_array("16", $errorCode) || in_array("237", $errorCode)) {
                $validationResult = true;
                $errorMessages = [];
                $errorCode = [];
            }
        }

        return $this->createResult($validationResult, $errorMessages, $errorCode);
    }
}
