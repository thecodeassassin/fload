<?php
/**
 * @author Stephen "TheCodeAssassin" Hoogendijk <admin@tca0.nl>
 */

namespace Fload;


use Fload\Middleware as Middleware;
use Fload\DataProvider\Exception as DataProviderException;

/**
 * Class DataProvider
 * @package Fload
 */
class DataProvider extends Middleware
{
    public function call()
    {

        $config = $this->getConfig();

        if (!array_key_exists('data_provider', $config)) {
            throw new Exception('No Data Provider set in config');
        }

        $this->registerDataProvider($config['data_provider']);
        $this->next->call();
    }

    /**
     * @param array $dataProvider
     *
     * @throws DataProvider\Exception
     */
    public function registerDataProvider(array $dataProvider)
    {
        $dsType = $dataProvider['type'];
        $dsClass = '\\Fload\\DataProvider\\'.$dsType;
        if (!class_exists($dsClass)) {
            throw new DataProviderException(sprintf('DataProvider %s does not exist!', $dsType));
        }

        // add the data provider to the dependency injector
        $this->app->di['data_provider'] = function ($c) use ($dsClass, $dataProvider) {
            $dataProviderObject = new $dsClass();
            $dataProviderObject->connect($dataProvider);
            return $dataProviderObject;
        };
    }

    /**
     * @return array
     */
    private function getConfig()
    {
        return $this->app->appConfig;
    }
}
