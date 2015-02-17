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
namespace App\File;

/**
 * Class Csv
 * @package App\File
 */
class Csv
{
    /**
     * File
     */
    protected $file;

    /**
     * Results
     */
    protected $results;

    /**
     * Constructor
     */
    function __construct($file)
    {
        // Save variables
        $this->file = $file;
        $this->results = [];
    }

    /**
     * Get file
     * @return string
     */
    public function getFile()
    {
        return $this->file;
    }

    /**
     * Get headers
     * @return array
     */
    public function getHeaders()
    {
        return [
            'Date',
            'Resent-Date',
            'From',
            'Resent-From',
            'Sender',
            'Resent-Sender',
            'Subject'
        ];
    }

    /**
     * Add row
     * @param $row
     */
    public function addRow($row)
    {
        // Add
        $this->results[] = $row;
    }

    /**
     * Write headers
     */
    public function writeHeaders()
    {
        $this->writeRow($this->getHeaders());
    }

    /**
     * Write file
     */
    public function writeFile()
    {
        // Open file
        $handle = fopen($this->getFile(), 'w+');
        if (empty($handle)) {
            return false;
        }

        // Write headers
        $headers = $this->getHeaders();
        array_unshift($headers, 'ID');
        fputcsv($handle, $headers);

        // Write rows
        $id = 1;
        foreach($this->results as $row) {

            // -- Id
            array_unshift($row, $id);
            $id++;

            // -- Write
            fputcsv($handle, $row);
        }

        // Close
        fflush($handle);
        fclose($handle);

        return true;
    }
}