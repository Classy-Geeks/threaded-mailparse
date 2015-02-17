<?php
/**
 * Copyright 2015 Classy Geeks llc. All Rights Reserved
 * http://classygeeks.com
 * MIT License:
 * http://opensource.org/licenses/MIT
 */

/**
 * Namespace
 */
namespace App\Threads;

use eXorus\PhpMimeMailParser\Parser;

/**
 * Class ParserThread
 * @package App\Threads
 */
class ParserThread extends BaseThread
{
    /**
     * File Csv
     */
    protected $file_csv = null;

    /**
     * File parse
     */
    protected $file_parse = null;

    /**
     * Row
     */
    protected $row = [];

    /**
     * @param $file_csv
     * @param $file_parse
     */
    function __construct($file_csv, $file_parse)
    {
        // Parent
        parent::__construct();

        // Save variables
        $this->file_csv = $file_csv;
        $this->file_parse = $file_parse;
    }

    /**
     * Get row
     * @return array
     */
    public function getRow()
    {
        return $this->row;
    }

    /**
     * Get result csv
     * @return App\File\Csv
     */
    public function getFileCsv()
    {
        return $this->file_csv;
    }

    /**
     * Get parse file
     * @return string
     */
    public function getFileParse()
    {
        return $this->file_parse;
    }

    /**
     * Run
     */
    protected function run()
    {
        try {

            // Output
            echo("INFO: Parsing msg file {$this->getFileParse()}" . EOL);

            // Parse message
            $parser = new Parser();
            $parser->setPath($this->getFileParse());

            // Columns (https://tools.ietf.org/html/rfc4021)
            $row = [];
            $headers = $this->getFileCsv()->getHeaders();
            foreach ($headers as $header) {

                // -- Get value, cleanse false
                $value = $parser->getHeader(strtolower($header));
                if (empty($value)) {
                    $row[$header] = '';
                }
                else {
                    $row[$header] = $value;
                }

            }

            // Save result
            $this->saveResult($row);
        }
        catch (\Exception $e) {

            // Output
            echo("ERROR: {$e->getMessage()}" . EOL);
        }
    }
}