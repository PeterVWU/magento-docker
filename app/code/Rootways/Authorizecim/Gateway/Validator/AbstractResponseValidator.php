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

use Magento\Payment\Gateway\Validator\AbstractValidator;

abstract class AbstractResponseValidator extends AbstractValidator
{
    const RESULTCODE = 'RESULTCODE';
    const RESPONSECODE = 'RESPONSECODE';
    const TRANSID = 'TRANSID';
    const AVSRESPCODE = 'AVSRESULTCODE';
    const CVV2RESPCODE = 'CVVRESULTCODE';
    const STATUSMSG = 'TEXT';

    protected function responseCode(array $response)
    {
        return isset($response['transactionResponse']['responseCode'])
            && ($response['transactionResponse']['responseCode'] == '1' || $response['transactionResponse']['responseCode'] == '4');
    }

    protected function approvalStatus(array $response)
    {
        return $response['messages']['resultCode'] == 'Ok';
    }

    protected function validateTxrefnum(array $response)
    {
        return isset($response['transactionResponse']['transId'])
            && $response['transactionResponse']['transId'] != '';
    }

    protected function validatePaymentProfileId(array $response)
    {
        return isset($response['customerProfileId'])
            && $response['customerProfileId'] != '' &&
            isset($response['customerPaymentProfileId'])
            && $response['customerPaymentProfileId'] != '';
    }

    protected function validationDirectResponse(array $response)
    {
        if (!empty($response['validationDirectResponse'])) {
            $validationResponse = explode(',', $response['validationDirectResponse']);
            if (!empty($validationResponse[6])) {
                return true;
            }
        }

        return false;
    }

    protected function validationVaultDirectResponse(array $response)
    {
        if (!empty($response['directResponse'])) {
            $validationResponse = explode(',', $response['directResponse']);
            if (!empty($validationResponse[6])) {
                return true;
            }
        }

        return false;
    }
}
