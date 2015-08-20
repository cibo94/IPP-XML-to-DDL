<?php
/**!
#XTD:xcibul10
 * \file argparser.php
 * \author Miroslav Cibulka
 * \brief
 * Tu sa parsuju argumenty
 */

/**!
 * \class Argument Parser
 * \brief Parsuje argumenty, pridava k nim ich hodnotu za = a pod
 */
class ARG_PARSER implements _ARG_PARSER
{

    private $argv;
    private $out;

    function __construct ($argv)
    {
        $this->argv = $argv;
        {
            $obj = new EVENT("Argument Parser Loaded!");
            $obj->log();
        }
    }

    public function parse ()
    {
        $this->out = array ();
        $match = array ();
        $argv = $this->argv;
        array_shift($argv);
        foreach ($argv as $arg) {
            if (preg_match('/^(?:-(\w)|--(\w{2,})(?:=[\'"]?([^"\']*)[\'"]?|=([^\s=]+)|))$/', $arg, $match) > 0) {
                if (isset($match[1]) && !isset($match[2]) && !isset($match[3])) {
                    $this->out[$match[1]] = true;
                } elseif (isset($match[2]) && (isset($match[3]) || isset($match[4]))) {
                    $this->out[$match[2]] = isset($match[4]) ? $match[4] : $match[3];
                } elseif (isset($match[2])) {
                    $this->out[$match[2]] = true;
                } else {
                    {
                        $obj = new EVENT("Zla forma argumentu '$arg'!");
                        $obj->error(APP::EXIT_PE);
                    }
                }
            } else {                        //!< neaka chyba v argumente hodi chybu
                {
                    $obj = new EVENT("Zly format argumentu '$arg'!");
                    $obj->error(APP::EXIT_PE);
                }
            }
        }
        return $this->out;
    }
}

?>
