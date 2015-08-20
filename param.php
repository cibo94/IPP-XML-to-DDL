<?php

#XTD:xcibul10
/**!
 * @file classes.php
 * @brief Su tu triedy popisujuce xml
 * @author Miroslav Cibulka - xcibul10
 */
class param implements _PARAM
{


    /**!
     * Public Values
     */
    public $pvalue;
    public $ptype;
    public $pname;

    /**!
     * Public Methods
     */
    function __construct ($val, $typ, $name)
    {
        $this->pvalue = $val;
        $this->ptype = $typ;
        $this->pname = $name;
    }

    function __toString ()
    {
        return self::conver($this->ptype);
    }

    public static function greater ($main, $cmp)
    {
        return self::__type($main) > self::__type($cmp);
    }

    public static function typeOf ($str, $text = false)
    {
        if ($str == "") {
            return self::convert(self::BIT);
        } elseif (preg_match("/^\s*(true|false|1|0)\s*$/i", $str) > 0) {
            return self::convert(self::BIT);
        } elseif (preg_match("/^\s*[+-]?[0-9]+\s*$/", $str) > 0) {
            return self::convert(self::INT);
        } elseif (preg_match("/^\s*[+-]?(([0-9]*[\.,][0-9]+|[0-9]+[\.,][0-9]*)([Ee][+-]?[0-9]+|)|[0-9]+[Ee][+-]?[0-9]+)\s*$/",
                $str) > 0
        ) {
            return self::convert(self::FLOAT);
        } elseif ($text) {
            return self::convert(self::NTEXT);
        } else {
            return self::convert(self::NVARCHAR);
        }
    }

    private static function __type ($str)
    {
        switch ($str) {
            case "BIT" :
                return self::BIT;
            case "INT" :
                return self::INT;
            case "FLOAT" :
                return self::FLOAT;
            case "NVARCHAR" :
                return self::NVARCHAR;
            default :
                return self::NTEXT;
        }
    }

    /**!
     * @brief funkcia preklada konstanty na text
     * @param $t je cislo/konstanta ktora znaci typ hodnoty
     * @return Typ v textovom tvare
     */
    private static function convert ($t)
    {
        $tmp = array (self::BIT => "BIT",
            self::INT => "INT",
            self::FLOAT => "FLOAT",
            self::NVARCHAR => "NVARCHAR",
            self::NTEXT => "NTEXT");
        return ($tmp[$t]);
    }
}

?>
