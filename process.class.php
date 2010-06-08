<?php
/**
 * Class to control the process.
 * @author Thiago Bocchile <tykoth@gmail.com>
 */
class Process {
    public $pipes = array();
    public $process;

    public function __construct($command) {
        return $this->open($command);
    }

    public function __destruct() {
        return $this->close();
    }

    public function open($command) {
        $spec = array(
                array("pty"),
                array("pty"),
                array("pty")
        );

        $this->process = proc_open($command, $spec, $this->pipes);
        $this->setBlocking(0);
    }

    public function isResource() {
        return is_resource($this->process);
    }
    public function setBlocking($blocking = 1) {
        return stream_set_blocking($this->pipes[1], $blocking);
    }
    public function getStatus() {
        return proc_get_status($this->process);
    }
    public function get() {
		$out = fread($this->pipes[1], 128);
//		$out = fgets($this->pipes[1]);
        //$out = stream_get_contents($this->pipes[1]);
        return $out;
    }
    public function put($data) {
//		fwrite($this->pipes[1], $data."\n");
        fwrite($this->pipes[1], $data);
//		fwrite($this->pipes[1], chr(13));
        fflush($this->pipes[1]);
//		return fwrite($this->pipes[1], $data);
    }

    public function sigTerm($term = 1) {
        return proc_terminate($this->process,$term);
    }
    public function close() {
        if(is_resource($this->process)) {
            fclose($this->pipes[0]);
            fclose($this->pipes[1]);
            fclose($this->pipes[2]);
            return proc_close($this->process);
        }
    }
    public function metaData() {
        return  stream_get_meta_data($this->pipes[1]);
    }
}
