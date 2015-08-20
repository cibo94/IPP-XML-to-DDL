<?php
/**!
#XTD:xcibul10
 * \file app.php
 * \author Miroslav Cibulka
 * \brief
 * Tu je volany parser a rozlisenie argumentov
 */
class APP implements _APP
{


    static public $DDL = STDOUT, $XML = STDIN;                 //!< File Handlers
    static public $input, $output, $header, $etc, $a, $b, $g;  //!< Parameters

    public function __construct ($argv)
    {

        $obj = new EVENT("     XML2DDL");
        $obj->log();
        $args = new ARG_PARSER($argv);
        $args = $args->parse();
        /*
            Osetrenie parametrov -> ak by napr chybala hodnota a pod
        */
        if (isset($args['help'])) {
            unset($args['help']);
            if (empty($args))
                self::help(0);
            else
                self::help(1);
        }
        if (isset($args['a'])) self::$a = $args['a'];
        unset($args['a']);
        if (isset($args['b'])) self::$b = $args['b'];
        unset($args['b']);
        if (isset($args['g'])) self::$g = $args['g'];
        unset($args['g']);
        if (isset($args['header'])) self::$header = $args['header'];
        unset($args['header']);
        if (isset($args['etc'])) {
            if ($args['etc'] >= 0)
                self::$etc = intval($args['etc']);
            else {
                $obj = new EVENT("ETC argument musi mat vecsiu hodnotu nez 0!");
                $obj->error(APP::EXIT_PE);
            }
            if (isset(self::$b)) {
                $obj = new EVENT("Argument ETC nesmie byt kombinovany s argumentom B!");
                $obj->error(APP::EXIT_PE);
            }
        }
        unset($args['etc']);
        if (isset($args['output'])) {
            if (file_exists($args['output'])) {
                $obj = new EVENT("Subor existuje - bude prepisany!");
                $obj->log();
            }
            if (!(self::$DDL = fopen($args['output'], "w"))) {
                $obj = new EVENT("Chyba pri otvoreni suboru! '" . $args['output'] . "'");
                $obj->error(APP::EXIT_ACCE);
            } else
                self::$output = $args['output'];
        }
        unset($args['output']);
        if (isset($args['input'])) {
            if (file_exists($args['input'])) {
                if (!(self::$XML = fopen($args['input'], "r"))) {
                    $obj = new EVENT("Chyba pri otvoreni suboru! '" . $args['input'] . "'");
                    $obj->error(APP::EXIT_ACCE);
                } else
                    self::$input = $args['input'];
            } else {
                $obj = new EVENT("Chyba pri otvarani suboru! '" . $args['input'] . "'");
                $obj->error(APP::EXIT_FNE);
            }
        }
        unset($args['input']);
        if (!empty($args)) foreach ($args as $key => $val) {
            $obj = new EVENT("Neznamy argument '$key'");
            $obj->error(APP::EXIT_PE);
        }

        {
            $obj = new EVENT("Main has been loaded! Running translator ...");
            $obj->log();
        }
    }

    function __destruct ()
    {
        {
            $obj = new EVENT("Exiting program ...");
            $obj->log();
        }
        fclose(self::$DDL);
        fclose(self::$XML);
    }

    private static function help ($ret)
    {
        printf("\t\tXML2DDL compiler\n");
        echo "
 \$ ./xml2ddl.php [--help|[--input=<fn>] [--output=<fn>] [--header=<hdr>] [--etc=<N>] [-a] [-b] [-g]]

    • --help tato napoveda
    • --input=filename zadaný vstupní soubor ve formátu XML
    • --output=filename zadaný výstupní soubor ve formátu definovaném výše
    • --header=’hlavička’ na začátek výstupního souboru se vloží zakomentovaná hlavička
    • --etc=n pro n ≥ 0 určuje maximální počet sloupců vzniklých ze stejnojmenných podelementů
    • -a nebudou se generovat sloupce z atributů ve vstupním XML souboru
    • -b pokud bude element obsahovat více podelementů stejného názvu, bude se uvažovat, jako
      by zde byl pouze jediný takový (tento parametr nesmí být kombinován s parametrem --etc=n)
    • -g lze jej uplatnit v kombinaci s jakýmikoliv jinými přepínači vyjma --help. Při jeho aktivaci
      bude výstupním souborem pouze XML tvaru\n\n";
        exit($ret);
    }
}

?>
