<?php
/**
 * @author Stephen "TheCodeAssassin" Hoogendijk <admin@tca0.nl>
 */

namespace Fload\Response;

/**
 * Class JsonResponse
 * @package Fload
 */
class JsonResponse
{
    const STATUS_FAILURE = 'failure';
    const STATUS_SUCCESS = 'success';


    const CODE_UNKNOWN_ERROR = 1001;
    const CODE_FILE_TOO_LARGE = 1002;
    const CODE_DATAPROVIDER_ERROR = 1003;
    const CODE_FLOAD_EXCEPTION = 1004;
    const PAGE_NOT_FOUND = 404;

    /**
     * @param       $code
     * @param       $message
     * @param array $result
     *
     * @throws \Exception
     * @return string
     */
    public static function response($code, $message, $result = array())
    {

        header('Content-Type: application/json');

        $response = array();
        $response['code'] = $code;
        $response['msg'] = $message;
        $response['result'] = $result;



        $response = json_encode($response);
        if (json_last_error() != JSON_ERROR_NONE) {
            throw new \Exception('Cannot encode parameters, check your input.');
        }

        return $response;

    }
}
