<?php
/**
 * @author Stephen "TheCodeAssassin" Hoogendijk <admin@tca0.nl>
*/

namespace Fload;

use Fload\Exception;
use Fload\Middleware;

/**
 * Main request handler for Fload
 *
 * Class Requests
 * @package Fload
 */
class Requests extends Middleware
{

    public function call()
    {
        $app = $this->app;

        /**
         * Register the requests
         */
        $app->get(
            '/:key',
            array(
                $this, 'getFileByKey'
            )
        );


        // regular put with an optional token
        $app->put(
            '/(:filename)',
            array(
                $this, 'uploadFile'
            )
        );

        $this->next->call();
    }

    /**
     * @param $key
     */
    public function getFileByKey($key)
    {

    }

    /**
     * Handle PUT request
     *
     * @param null $filename
     *
     * @throws Exception
     */
    public function uploadFile($filename = null)
    {
        $secretKey = $this->app->appConfig['secret_key'];
        $dataProvider = $this->getDataProvider();

        if (!$secretKey) {
            throw new Exception('Invalid secret key!');
        }

        $authenticated = false;
        $token = base64_decode($filename);

        $parts = explode('-', $token);
        if (count($parts) == 2 && is_numeric($parts[1])) {
            $future = strtotime('+10 minutes');

            if ($future < $parts[1] || $parts[0] != $secretKey) {
                throw new Exception('Invalid authentication token send!');
            }
        }

        $firstSum = sha1('sda');

        if ($dataProvider->exists('files', 'firstSum', $firstSum)) {

        } else {
            $dataProvider->insert(
                'files',
                array(
                    'firstSum' => $firstSum,
                    'fileName' => $filename
                )
            );
        }

        die('ass');
    }

}
