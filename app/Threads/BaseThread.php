<?php
/**
 * Copyright 2016 Matthew R. Miller via Classy Geeks llc. All Rights Reserved
 * http://classygeeks.com
 * MIT License:
 * http://opensource.org/licenses/MIT
 */

/**
 * Namespace
 */
namespace App\Threads;


/**
 * Class BaseThread
 * @package App\Threads
 */
class BaseThread
{
    /**
     * Max size
     */
    protected $max_size = 0x40000;

    /**
     * The Id of the shared memory block
     * @var long
     */
    protected $shm_key;

    /**
     * The next id of the shared memory block available
     * @var type
     */
    protected static $last_shm_key = 0xf00;

    /**
     * PID
     */
    protected $pid = 0;

    /**
     * Id
     */
    protected $id;

    /**
     * Constructor
     * @param $file_res
     * @param $file_parse
     */
    function __construct()
    {
        // Function check
        if (!function_exists('pcntl_fork')) {
            throw new \RuntimeException('PHP was compiled without --enable-pcntl or you are running on Windows.');
        }

        // Generate id and shared memory key
        $this->shm_key = self::$last_shm_key++;
        $this->id = hash('sha1', uniqid() . time());
    }

    /**
     * Get pid
     * @return integer
     */
    public function getPid()
    {
        return $this->pid;
    }

    /**
     * Get id
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Check if the forked process is active
     * @return bool
     */
    public function isActive()
    {
        return (pcntl_waitpid($this->getPid(), $status, WNOHANG) === 0);
    }

    /**
     * Handle the signal to the thread
     *
     * @param int $signal
     */
    private function signalHandler($signal)
    {
        switch ($signal) {
            case SIGTERM:
                exit(0);
        }
    }

    /**
     * Stop thread
     *
     * @param int $signal
     * @param bool $wait
     */
    public function stop($signal = SIGKILL, $wait = false)
    {
        // Must be alive
        if ($this->isAlive()) {

            // -- Kill
            posix_kill($this->getPid(), $signal);

            // -- Wait?
            if ($wait) {
                pcntl_waitpid($this->getPid(), $status);
            }
        }
    }

    /**
     * Start the thread
     *
     * @throws RuntimeException
     */
    public function start()
    {
        // Start fork
        if (($this->pid = pcntl_fork()) == -1) {
            throw new \RuntimeException('Couldn\'t fork the process');
        }

        // Don't start twice
        if ($this->pid) {
        }
        else {

            // -- Child
            pcntl_signal(SIGTERM, array($this, 'signalHandler'));

            // -- Run
            $this->run();

            // -- Exit gracefully
            exit(0);
        }
    }

    /**
     * Run
     */
    protected function run()
    {
    }

    /**
     * Save the result in a shared memory block
     *
     * @param mixed $object Need to be serializable
     */
    protected function saveResult($object)
    {
        // Serialize
        $serialized = serialize($object);
        $size = strlen($serialized);

        // Proper size
        if ($size < $this->max_size) {

            // -- Open handle
            $shm_id = @shmop_open($this->shm_key, 'c', 0644, $size);
            if (!$shm_id) {
                throw new \Exception('Couldn\'t create shared memory segment');
            }

            // -- Check length
            if (shmop_write($shm_id, $serialized, 0) != $size) {
                throw new \Exception('Couldn\'t write the entire length of data');
            }

            // Close
            shmop_close($shm_id);
        }
        else {
            throw new \OverflowException('The response of the thread was greater then ' . $this->max_size . ' bytes.');
        }
    }

    /**
     * Get result
     * @return mixed|null
     */
    public function getResult()
    {
        // Open result
        $shm_id = @shmop_open($this->shm_key, 'a', 0644, $this->max_size);
        if (!$shm_id) {
            return null;
        }

        // Read
        $serialized = shmop_read($shm_id, 0, shmop_size($shm_id));
        shmop_delete($shm_id);
        shmop_close($shm_id);

        // Return
        return unserialize($serialized);
    }
}