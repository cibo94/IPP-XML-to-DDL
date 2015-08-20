<?php

#XTD:xcibul10

/**!
 * @file lexic.php
 * @author Miroslav Cibulka
 * @brief Lexikalna analyza
 **/
class LEXIC implements _LEXIC
{

    private $handle;        //!< Input file
    private $file;          //!< Meno suboru
    private $tags;          //!< array tagov v multidimenzionalnom regularnom poli
    private $i = 0;         //!< globalny pocitac
    private $params;        //!< parametre tagov
    private $TagStack = array ();
    //!< Stack na ulozenie tagov
    private $parent;        //!< Otcovsky tag
    private $xml;           //!< vystupny XML dokument ako objekt (class XML)

    public function getXML ()
    {
        return $this->xml;
    }

    /**!
     * @brief Checks XML validity (but not syntax =( )
     * @param $src Source file readed fully to string
     * @param $cpy Parsed source with regular expression and again connected
     */
    private function checkValidity ($src, $cpy)
    {
        $cpy_i = 0;
        $err_string = "";
        $was_err = false;
        $err_char = 0;
        $line_char = 0;
        $err_report = "";
        $error = false;
        $LINE = 0;
        foreach (str_split($src) as $ch) {
            while (isset($cpy[$cpy_i]) && ctype_space($cpy[$cpy_i])) $cpy_i++;
            $err_string .= $ch;
            if (ctype_space($ch)) {
                if ($ch == "\n") {
                    $LINE++;
                    if ($was_err) {
                        $err_string = substr($err_string, 0, -1);
                        $err_report .= "$this->file:$LINE:$err_char: $err_string\n";
                        $err_report .= str_repeat(" ", strlen($this->file) + strlen("$LINE") + strlen("$err_char") + 4 + $err_char) . "\e[0;31m^\e[0m\n";
                        $was_err = false;
                    }
                    $err_string = "";
                    $line_char = 0;
                }
                continue;
            }
            if (!isset($cpy[$cpy_i]) || $cpy[$cpy_i] != $ch) {              //!< Tu sa najde chyba
                $err_char = $line_char;
                $was_err = true;
                $error = true;
            } else
                $cpy_i++;
            $line_char++;
        }
        if (isset($cpy[$cpy_i]))
            ($event = new EVENT("\e[0;31mUnexpected end of file:\e[0m\n$err_report\n") and $event->error(APP::EXIT_BFE));
        if ($error)
            ($event = new EVENT("\e[0;31mErrors have been detected:\e[0m\n$err_report\n") and $event->error(APP::EXIT_BFE));
    }

    /**!      TRANSFROM TO XML
     * @brief Nacita cely subor a prevedie ho na viacrozmerne pole podla regularneho vyrazu
     * @return class XML ako globalnu premennu $this->xml
     */
    private function transform ()
    {
        $source = self::getContent($this->handle, $this->file);
        preg_match_all('/(?:<\?.*\?>\s*|<(\/?[a-zA-Z_][\w\d-]*)\s*(?:>\s+(?=<)|>([^<>]*)(?=<)|((?:\s+[a-zA-Z_][\w\d-]*\s*=\s*[\'"][^\'"]*[\'"])+)\s*(?:(?:>\s+(?=<)|>([^<>]*)(?=<))|(\/)>)|(\/)?>))/',
            $source, $this->tags);
        $this->checkValidity($source, implode("", $this->tags[0]));
        $this->xml = new XML(self::arrayTranspose($this->tags));
        $this->xml->connect();
    }

    /**!    Get CONTENT of XML file
     * @brief funkcia ktora vracia cely obsah suboru tj vyrieseny problem so STDIN
     * @param $handle File handle
     * @param $name Meno suboru ktory chceme nacitat (koli velkosti suboru)
     */
    private static function getContent ($handle, $name)
    {
        if ($handle == STDIN) {
            $out = "";
            while (!feof($handle)) {
                $out .= fgetc($handle);
            }
            return $out;
        } else {                                        //!< alebo aj nie
            if (filesize($name) == 0)                      //!< mozno ide o STDIN ...
            {
                $obj = new EVENT("Bad File Handle!");
                $obj->error(APP::EXIT_BFE);
            } else
                return fread($handle, filesize($name));
        }
    }

    /**!     ARRAY TRANSITION
     * @brief Usporiada prvky spravnym 'natocenim' pola podla mena tagu
     * @return $nodes Usporiadane pole tagov
     */
    private static function arrayTranspose ($tags)
    {
        $nodes = array ();
        for ($i = 0; $i < count($tags[0]); $i++) {      //!< iteracia cez vsetky TAGY!
            if ($tags[self::TAG][$i] != "")
                array_push($nodes, new node(strtolower($tags[self::TAG][$i]), strtolower($tags[self::VAL][$i]),
                    strtolower($tags[self::PARAM][$i]), strtolower($tags[self::PARNVAL][$i]), ($tags[5][$i] ? true : ($tags[6][$i] ? true : false))));
            //!< konvertovanie do nodu a prichanie do pola
        }
        return $nodes;
    }

    function __construct ($handle, $filename = null)
    {
        if ($handle == false)
            ($err = new EVENT("File do not exists!") and $err->error(APP::BFE));
        {
            $obj = new EVENT("Loading LEXiC!");
            $obj->log();
        }
        $filename = stream_get_meta_data($handle);
        $filename = $filename['uri'];
        $this->handle = $handle;
        $this->file = $filename;
        $this->transform();
    }

    function __destruct ()
    {
        {
            $obj = new EVENT("Destructing LEXiC!");
            $obj->log();
        }
    }
}

?>
