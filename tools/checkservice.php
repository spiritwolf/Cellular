<?php
/**
 *
 */
class checkService {

    private $error;

    function __construct(argument)
    {
        $this->error = true;
        $this->check();
        if (false !== $this->error) {
            echo 'ok';
        }
    }

    private check()
    {
        if (!function_exists('mb_strtolower')) {
            $this->output('Not install mbString');
            $this->error = false;
        }
    }

    private function output($msg)
    {
        echo $msg.PHP_EOL;
    }

}

new checkService();

?>
