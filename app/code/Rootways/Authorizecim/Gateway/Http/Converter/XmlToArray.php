<?php
namespace Rootways\Authorizecim\Gateway\Http\Converter;

use Magento\Payment\Gateway\Http\ConverterException;
use Magento\Payment\Gateway\Http\ConverterInterface;

class XmlToArray implements ConverterInterface
{
    public function convert($result)
    {
        /*
        $parser = xml_parser_create('UTF-8');
        xml_parse_into_struct($parser, $result, $response);
        $resData = array();
        foreach ( $response as $v ) {
           if ( isset($v["tag"]) && isset($v["value"]) ) {
                $resData[$v["tag"]] = $v["value"];
            }
        }
        // echo '<pre>';print_r($resData);exit;
        if (!isset($resData['APPROVALSTATUS'])) {
            $errorMsg = 'There is an error in payment processing. Please try again with correct credit card detail.';
            if (isset($resData['STATUSMSG'])){
                $errorMsg = 'There is an error in payment processing. Error Message From Payment Gateway is, '. $resData['STATUSMSG'];
            }
            throw new ConverterException(__($errorMsg));
        } else {
            if ($resData['PROCSTATUS'] == 0 && $resData['APPROVALSTATUS'] == 1) {
               return $resData;
            } else {
                 $errorMsg = 'There is an error in payment processing. Please try again with correct credit card detail.';
                if (isset($resData['STATUSMSG'])) {
                    $errorMsg = 'There is an error in payment processing. Error Message From Payment Gateway is, '. $resData['STATUSMSG'];
                }
                throw new ConverterException(__($errorMsg));
            }
        }
        */
    }
}
