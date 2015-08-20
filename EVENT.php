<?php
/**!
#XTD:xcibul10
 * \file error.php
 * \author Miroslav Cibulka
 * \brief
 * Modul pre errorove hlasenia
 */
class EVENT implements _EVENT
{

    private $msg,                   //!< Sprava
        $ERR_TYPE;              //!< Chybovy kod pre error
    static public $DEBUG = true;          //!< Skrytie/zobrazenie logovacich vypisov

    /**!
     * Konstruktor -> nastavi err typ, spravu, default timezone, a vypise traceback
     */
    function __construct ($msg = "No message to report")
    {
        $this->msg = $msg;
        $this->ERR_TYPE = array (
            APP::EXIT_PE => "ParamError",
            APP::EXIT_FNE => "FileNotExist",
            APP::EXIT_ACCE => "AccessError",
            APP::EXIT_BFE => "BadFormatError",
            APP::EXIT_INTERNAL => "InternalError"
            /* TODO: Dorobit errory */
        );
        date_default_timezone_set("Europe/Bratislava");
    }

    /**!
     * Vypise chybu, ukonci program
     */
    public function error ($err_code)
    {
        $err = "\n";
        $bts = defined('DEBUG_BACKTRACE_IGNORE_ARGS') ?
            debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS) :
            debug_backtrace(false);
        if (self::$DEBUG) {
            foreach ($bts as $bt) {
                $err .= $bt["file"] . ":" . $bt["line"] . ":" . $bt["class"] . $bt["type"] . $bt["function"] . "();\n";
            }
        }
        $err .= "ERROR: " . $this->msg;
        if (isset($this->ERR_TYPE[$err_code]))
            $err .= "\n\tERROR TYPE: " . $this->ERR_TYPE[$err_code] . "\n\n";
        else $err .= "\n\n";
        throw new Exception($err, $err_code);
    }

    /**!
     * Logovacia funkcia
     */
    public function log ($traceback = false)
    {
        $bts = defined('DEBUG_BACKTRACE_IGNORE_ARGS') ?
            debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS) :
            debug_backtrace(false);
        if (self::$DEBUG)
            if ($traceback) {
                fprintf(STDERR, "\n");
                $i = 1;
                foreach ($bts as $bt) {
                    fprintf(STDERR, " %s:%d: @%02d %s%s%s();\n", $bt["file"], $bt["line"], $i++,
                        $bt["class"], $bt["type"], $bt["function"]);
                }
                fprintf(STDERR, "{ %s | Log }\t%s\n\n", date("H:i:s", time()), $this->msg);
            } else {
                $caller = $bts[1];
                if (isset($caller))
                    $caller = $bts[0];
                fprintf(STDERR, $caller['file'] . ":" . $caller["line"] . ": %s\n", $this->msg);
            }
    }
}

?>
