<?php
/**
 * @author Stephen "TheCodeAssassin" Hoogendijk <admin@tca0.nl>
 */

namespace Fload;

use Fload\DataProvider\DataInterface;

/**
 * Class Middleware
 * @package Fload
 */
abstract class Middleware extends \Slim\Middleware
{
    /**
     * @return DataInterface
     */
    public function getDataProvider()
    {
        return $this->app->di['data_provider'];
    }
}
