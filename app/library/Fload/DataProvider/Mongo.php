<?php
/**
 *  @author Stephen "TheCodeAssassin" Hoogendijk <admin@tca0.nl>
 */

namespace Fload\DataProvider;

use MongoDB as DB;

/**
 * Class Mongo
 * @package Fload\DataProvider
 */
class Mongo implements DataInterface
{

    protected $config;

    /**
     * @var DB
     */
    protected $db;
    protected $client;

    /**
     * Connect to the mongodb server
     *
     * @param array $connectionDetails
     *
     * @return mixed|void
     */
    public function connect(array $connectionDetails)
    {

        $mongoClient = new \MongoClient(
            sprintf(
                'mongodb://%s:%s',
                $connectionDetails['host'],
                $connectionDetails['port']
            ),
            $connectionDetails['options']
        );
        $this->db = $mongoClient->selectDB($connectionDetails['db']);
        $this->client = $mongoClient;
    }

    /**
     * @param       $collection
     * @param array $document
     *
     * @return array|bool|mixed
     */
    public function insert($collection, array $document)
    {
        return $this->db->selectCollection($collection)->insert($document);
    }

    /**
     * @param       $collection
     * @param array $changes
     * @param       $key
     * @param       $value
     *
     * @return mixed|void
     */
    public function update($collection, array $changes, $key, $value)
    {

    }

    /**
     * @param $collection
     * @param $key
     * @param $value
     *
     * @return mixed|void
     */
    public function delete($collection, $key, $value)
    {

    }

    /**
     * @param $collection
     * @param $key
     * @param $value
     *
     * @return \MongoCursor
     */
    public function find($collection, $key, $value)
    {
        return $this->findBy($collection, array($key => $value));
    }

    /**
     * @param       $collection
     * @param array $parameters
     *
     * @return \MongoCursor
     */
    public function findBy($collection, array $parameters)
    {
        $result = $this->db->selectCollection($collection)->find(
            $parameters
        );

        return $result;
    }

    /**
     *
     *
     * @param $collection
     * @param $key
     * @param $value
     *
     * @return bool
     */
    public function exists($collection, $key, $value)
    {
        $result = $this->find($collection, $key, $value);

        if ($result->count() > 0) {
            return true;
        }

        return false;
    }

    /**
     * Authenticate to the mongoDB database
     *
     * @param $username
     * @param $password
     *
     * @return array
     */
    protected function authenticate($username, $password)
    {
        return $this->db->authenticate($username, $password);
    }
}
