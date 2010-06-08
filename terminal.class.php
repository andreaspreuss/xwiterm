<?php
/**
 * Terminal
 * A Class Terminal é a classe responsável pela camada de interação entre
 * os processos e o usuário.
 * Depois eu vou traduzir todos os comentários para o inglês...
 * agora não... agora não... depois, depois, depois... (preguiça).
 * @author Thiago Bocchile <tykoth@gmail.com>
 */

class Terminal {

    // Pra entrar no terminal precisa, óbvio.
    private $login;
    private $password;

    static $commandsFile = "comandos.txt";
    static $totalCommands;
    static $process;
    static $status;
    static $meta;

    static $instance;
    public static function autenticate($login, $password) {
        $process = new Process("su " . escapeshellarg($login));
        usleep(500000);
        $process->put($password);
        $process->put(chr(13));
        usleep(500000);
        return (bool) !$process->close();
    }

    public static function run($login, $password){
        self::$instance = new self();
        return self::$instance->open($login, $password);
    }
    public static function postCommand($command){
        file_put_contents(self::$commandsFile, $command."\n", FILE_APPEND);
    }

//    public  function __construct($login, $password) {
//        return $this->open($login, $password);
//    }
    private function open($login, $password) {
        $this->login = $login;
        $this->password = $password;

        if(!is_writable(self::$commandsFile)){
            $this->output("\r\nNeed permission to write in ".self::$commandsFile."\r\n");
            return false;
        }

        // Clean commands
        file_put_contents(self::$commandsFile, "");
        $this->startProcess();
        do {
            $out = self::$process->get();

            // Detect "blocking" (wait for stdin)
            if(sizeof($out) == 1 && ord($out) == 0) {
                $this->listenCommand();
            } else {
                // Provisorio, meldels. (usuario www-data não tem controle de servico, dude!)
                if(preg_match('/-su: no job control in this shell/', $out)) continue;
                $this->output($out);
            }
            usleep(50000);
            self::$status = self::$process->getStatus();
            self::$meta = self::$process->metaData();
        } while(self::$meta['eof'] === false);
    }

    private function startProcess() {
        self::$process = new Process("su - {$this->login}");
//        self::$process = new Process("vi");
        if(!self::$process->isResource()) {
            throw new Exception("recurso indisponivel");
            return false;
        }
        usleep(500000);
        self::$process->put($this->password);
        self::$process->put(chr(13));
        self::$process->get();
        usleep(500000);
        self::$status = self::$process->getStatus();
        self::$meta = self::$process->metaData();
    }

    private function output($output) {

        $output = htmlentities($output);
        // nl2br doesn't works...
        $output = explode("\n", $output);
        $output = implode("</span><span>", $output);
        $output = sprintf("<span>%s</span>", $output);
        $output = preg_replace( "/\n|\r|\r\n/", '\n', $output);
//        var_dump($output);die;
        $output = trim($output);
//        $output = addslashes($output);
        echo "<script>recebe(\"{$output}\");</script>\n";
        flush();
    }

    /**
     * Listen for incoming commands
     */
    private function listenCommand() {

        $commands = file(self::$commandsFile);
//        $this->output("w84cmm");
        if(sizeof($commands) > self::$totalCommands) {
            self::$totalCommands = sizeof($commands);
            $command = $commands[self::$totalCommands-1];
            $this->parse($command);
        }
    }

    /**
     * Parse the command
     */
    private function parse($command) {
        switch(trim($command)) {
            case chr(3):
            // SIGTERM
                return $this->sendSigterm();
                break;
            case chr(4):
                self::$process->put("exit");
                self::$process->put(chr(13));
                break;

            case chr(26):
            //STOP - experimental
                return $this->sendSigstop();
                break;
            case 'fg':
                return $this->sendFg();
                break;
            case 'bg':
                return $this->sendBg();
                break;
            default:
                self::$process->put($command);
                usleep(500000);
                break;
        }
    }


    /**
     * Emulates the SIGTERM sending via CTRL-C
     */
    private function sendSigterm() {
        // SLAYER!!! GRRRRRRRRRR
        // http://www.youtube.com/watch?v=VSoh3c7QVyw
        $SLAYER = 'pid='.self::$status['pid'].
        '; supid=`ps -o pid --no-heading --ppid $pid`;'.
        'bashpid=`ps -o pid --no-heading --ppid $supid`;'.
        'childs=`ps -o pid --no-heading --ppid $bashpid`;'.
        'kill -9 $childs;';
        $process = new Process("su -c '{$SLAYER}' -l {$this->login}");
        usleep(500000);
        $process->put($this->password);
        $process->put(chr(13));
        usleep(500000);
    }

    private function sendSigstop() {
        $SLAYER = 'pid='.self::$status['pid'].
        '; supid=`ps -o pid --no-heading --ppid $pid`;'.
        'bashpid=`ps -o pid --no-heading --ppid $supid`;'.
        'childs=`ps -o pid --no-heading --ppid $bashpid`;'.
        'kill -TSTP $childs;';
        $process = new Process("su -c '{$SLAYER}' -l {$this->login}");
        usleep(500000);
        $process->put($this->password);
        $process->put(chr(13));
        self::$process->put(chr(13));
        usleep(500000);
    }
    private function sendBg() {
        $SLAYER = 'pid='.self::$status['pid'].
        '; supid=`ps -o pid --no-heading --ppid $pid`;'.
        'bashpid=`ps -o pid --no-heading --ppid $supid`;'.
        'childs=`ps -o pid --no-heading --ppid $bashpid`;'.
        'kill -TSTP $childs;';
        $process = new Process("su -c '{$SLAYER}' -l {$this->login}");
        usleep(500000);
        $process->put($this->password);
        $process->put(chr(13));
        self::$process->put(chr(13));
        usleep(500000);
    }
    private function sendFg() {
        $SLAYER = 'pid='.self::$status['pid'].
        '; supid=`ps -o pid --no-heading --ppid $pid`;'.
        'bashpid=`ps -o pid --no-heading --ppid $supid`;'.
        'childs=`ps -o pid --no-heading --ppid $bashpid`;'.
        'kill -CONT $childs;';
        $process = new Process("su -c '{$SLAYER}' -l {$this->login}");
        usleep(500000);
        $process->put($this->password);
        $process->put(chr(13));
        self::$process->put(chr(13));
        usleep(500000);
    }
}
