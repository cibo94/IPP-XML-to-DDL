<?php

#XTD:xcibul10
/**!
 * \file xml2ddl.php
 * \author Miroslav Cibulka
 * \brief
 * V tomto subore su ulozene vsetky zakladne volania programu
 * a includy
 */
class APPLICATION extends APP
{

    function main ()
    {
        $c = microtime(true);
        (($obj = new LEXIC(parent::$XML, parent::$input)) and ($XML = $obj->getXML()));
        $XML->HEADER = parent::$header;
        $XML->ETC = parent::$etc;
        $XML->G = parent::$g;
        $XML->A = parent::$a;
        $XML->B = parent::$b;
        $b = microtime(true);
        $DDL = $XML->convertToDDL();
        $a = microtime(true);
        fprintf(parent::$DDL, $DDL);
        $d = microtime(true);
#            echo (($a-$b)*1000000)."micros ku ".(($d-$c)*1000000)."micros\n";
        return APP::EXIT_SUCCESS;
    }
}

?>
