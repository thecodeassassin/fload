<?php
/**
 * @author Stephen "TheCodeAssassin" Hoogendijk <admin@tca0.nl>
 */
namespace Fload\Log;

use Slim\Log;

/**
 * Class Writer
 * @package Fload\Log
 */
class Writer extends DateTimeFileWriter
{

    /**
     * @param array $settings
     */
    public function __construct($settings = array())
    {
        if (!is_writable($settings['path'])) {
            throw new \Exception(sprintf('Path %s is not writable!', $settings['path']));
        }

        parent::__construct($settings);
    }

    /**
     * Custom log writer, does some additional checks
     * @param mixed $object
     * @param int   $level
     */
    public function write($object, $level)
    {
        //Determine label
        $label = 'DEBUG';
        switch ($level) {
            case Log::FATAL:
                $label = 'FATAL';
                break;
            case Log::ERROR:
                $label = 'ERROR';
                break;
            case Log::WARN:
                $label = 'WARN';
                break;
            case Log::INFO:
                $label = 'INFO';
                break;
        }

        //Get formatted log message
        $message = str_replace(
            array(
                '%label%',
                '%date%',
                '%message%'
            ),
            array(
                $label,
                date($this->settings['line_date_format']),
                (string)$object
            ),
            $this->settings['message_format']
        );

        //Open resource handle to log file
        if (!$this->resource) {
            $filename = date($this->settings['name_format']);
            if (! empty($this->settings['extension'])) {
                $filename .= '.' . $this->settings['extension'];
            }
            $logFile = $this->settings['path'] . DIRECTORY_SEPARATOR . $filename;

            if (!file_exists($logFile)) {
                touch($logFile);
                chmod($logFile, 0777);
            }

            $this->resource = fopen($logFile, 'a');
        }

        //Output to resource
        fwrite($this->resource, $message . PHP_EOL);
    }
}
