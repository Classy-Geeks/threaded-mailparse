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
namespace App\App;

use App\File\Csv;
use App\Threads\ParserThread;

/**
 * Class ParserApp
 * @package App\App
 */
class ParserApp
{
    /**
     * Threads
     */
    protected $threads = [];

    /**
     * Path scan
     */
    protected $path_scan = null;

    /**
     * File result
     */
    protected $file_res = null;

    /**
     * File Csv
     */
    protected $file_csv = null;

    /**
     * Get result csv
     * @return App\File\Csv
     */
    public function getFileCsv()
    {
        return $this->file_csv;
    }

    /**
     * Get dispatcher
     * @return Amp\Thread\Dispatcher
     */
    public function getThreads()
    {
        return $this->threads;
    }

    /**
     * Get scan path
     * @return string
     */
    public function getScanPath()
    {
        return $this->path_scan;
    }

    /**
     * Get result file
     * @return string
     */
    public function getFileResult()
    {
        return $this->file_res;
    }

    /**
     * Get number active threads
     * @return integer
     */
    private function getNumActiveThreads()
    {
        // Calculate the number of threads
        $total = 0;
        foreach ($this->threads as $id => $thread) {

            // -- Active?
            if ($thread->isActive()) {
                $total++;
            }
            else {

                // -- Get results
                $results = $this->threads[$id]->getResult();
                if (!empty($results)) {
                    $this->getFileCsv()->addRow($results);
                }

                // -- Delete
                unset($this->threads[$id]);
            }
        }

        return $total;
    }

    /**
     * Wait for active threads
     * @param $max
     */
    private function _waitActiveThreads($max)
    {
        // Idle while max active
        while ($this->getNumActiveThreads() >= $max) {

            // -- Sleep
            usleep(200);
        }
    }

    /**
     * Run
     */
    public function run()
    {
        try {

            // Command line arguments
            $options = [
                'path-scan:',
                'file-res:',
                'num-threads::',
                'exec-limit::',
                'memory-mb::'
            ];
            $args = getopt('', $options);

            // validate scan path
            if (!isset($args['path-scan']) || !is_dir($args['path-scan'])) {
                throw new \Exception('Invalid --path-scan argument passed' . EOL);
            }

            // Validate result file
            if (!isset($args['file-res'])) {
                throw new \Exception('Invalid --file-res argument passed' . EOL);
            }

            // Save paths
            $this->path_scan = realpath($args['path-scan']);
            $this->file_res = realpath(dirname($args['file-res'])) . DS . basename($args['file-res']);

            // Prepare output file
            $this->file_csv = new Csv($this->getFileResult());

            // Number of threads
            $args['num-threads'] = (isset($args['num-threads']) ? $args['num-threads'] : 20);
            $args['exec-limit'] = (isset($args['exec-limit']) ? $args['exec-limit'] : -1);
            $args['memory-mb'] = (isset($args['memory-mb']) ? $args['exec-limit'] : 512);

            // Set memory limits
            ini_set('memory_limit', "{$args['memory-mb']}M");

            // Set execution time limit
            set_time_limit($args['exec-limit']);

            // Output
            echo("INFO: Scanning path {$this->getScanPath()}" . EOL);

            // Scan the directory
            $num_msg = 0;
            $dir = new \RegexIterator(new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($this->getScanPath())), '/^.+\.(msg|eml)/i', \RecursiveRegexIterator::GET_MATCH);
            foreach ($dir as $file) {

                // -- Find file
                if (!isset($file[0])) {
                    continue;
                }
                $file = $file[0];

                // -- Wait for active threads
                $this->_waitActiveThreads($args['num-threads']);

                // -- Create thread
                $num_msg++;
                $thread = new ParserThread($this->getFileCsv(), $file);
                $thread->start();
                $this->threads[$thread->getId()] = $thread;
            }

            // Wait for all threads to complete
            while (($count = $this->getNumActiveThreads()) != 0) {

                // -- Output
                echo("INFO: Waiting for {$count} parsing threads to finish." . EOL);

                // -- Sleep
                usleep(200);
            }

            // Write results
            if (!$this->getFileCsv()->writeFile()) {
                throw new \Exception('Unable to write results file.');
            }

            // Output result file
            echo(EOL . "Results file for {$num_msg} files: " . EOL);
            echo(EOL . file_get_contents($this->getFileResult()) . EOL);

        }
        catch (\Exception $e) {

            // Output
            echo("ERROR: {$e->getMessage()}" . EOL);

        }
    }
}