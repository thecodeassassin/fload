<?php
/**
 *  @author Stephen "TheCodeAssassin" Hoogendijk <admin@tca0.nl>
 */

namespace Fload\DataProvider;

/**
 * Interface DataInterface
 * @package Fload\DataProvider
 */
interface DataInterface
{
    /**
     * This method should implement connecting to the database
     * @param array $connectionDetails
     *
     * @return mixed
     */
    public function connect(array $connectionDetails);

    /**
     * This method should implement inserting into the database
     *
     * @param       $table
     * @param array $row
     *
     * @return mixed
     */
    public function insert($table, array $row);

    /**
     * This method should implement updating a record into the database
     *
     * @param       $table
     * @param array $changes
     * @param       $key
     * @param       $identifier
     *
     * @return mixed
     */
    public function update($table, array $changes, $key, $identifier);

    /**
     * This method should implement deleting a record in the database
     *
     * @param $table
     * @param $key
     * @param $identifier
     *
     * @return bool
     */
    public function delete($table, $key, $identifier);

    /**
     * This method should implement finding a record in the database
     *
     * @param $table
     * @param $key
     * @param $identifier
     *
     * @return mixed
     */
    public function find($table, $key, $identifier);

    /**
     * This method should implement the verification of a record's existence
     *
     * @param $table
     * @param $key
     * @param $identifier
     *
     * @return mixed
     */
    public function exists($table, $key, $identifier);
}
