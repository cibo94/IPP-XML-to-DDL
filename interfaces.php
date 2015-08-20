<?php

#XTD:xcibul10
/**!
 * @file interfaces.php
 * @author Miroslav Cibulka
 * @brief
 *      Modul kde su vsetky interfaces (prototypovanie)
 */

/**!    EVENT interface
 * @class obsahuje funkcie na report chyb a logovanie
 */
interface _EVENT
{

    /**!    ERROR EVENT
     * @brief funkcia reportuje chybu na stderr a ukonci program s error kodom
     * @param $err_code parameter ktory sa preklada na spravu a je zaroven navratovym kodom
     */
    public function error ($err_code);

    /**!    LOG EVENT
     * @brief funkcia zahlasi spravu na stderr a neukonci program
     *          ... ak je vypnuty debug neloguje
     * @param $traceback zapnutie trasovania volania funckie (napise z kade bol log zavolany)
     *              error($err_code); je zapnute nativne
     */
    public function log ($traceback = false);
}

/**!    DDL interface
 * @class popisuje DDL subor
 */
interface _DDL extends _XML
{
    const E = 0;
    const NTO = 1;
    const OTN = 2;
    const NTM = 3;
    const OTO = 4;

    /**!        Cetnost node v stromoch
     * @brief Zisti maximalnu cestnost nodu v strome a jeho synonymach
     * @param $tree pole stromov (synonyma) v ktorom budeme ratat nody
     * @param $node ratany prvok
     * @return maximalnu pocetnost nodu v synonymach stromu
     */
    public static function maxCetnost ($tree, $node);
}

/**!    XML interface
 * @class predstavuje format XML v strukture
 *         obsahuje node-y ktore su vzajomne previazane (node ma deti a otca)
 *
 */
interface _XML
{

    const XML = 0;                  //!< Format vypisu XML
    const OBJECT = 1;               //!< Format vypisu struktura XML objektu

    /**!    Vracia XML strom
     * @brief Vracia prvy node == koren stromu
     */
    public function getTree ();

    /**!    Almost DDL  (XML -> XML)
     * @brief Skonvertuje XML do XML s upravenou strukturov
     *         pripravenou na prechod do DDL
     */
    public function &convertToDDL ();

    /**!  Rekurzivny prechod PreOrder
     * @brief spravi PreOrder prechod!
     * @param $root Root stromu
     * @param $function Je to funckia ktora ma tvar function(child, args)
     * @param $args pole argumentov
     */
    public static function preOrder ($root, $function, &$args);

    /**!  Rekurzivny prechod PostOrder
     * @brief spravi PreOrder prechod!
     * @param $root Root stromu
     * @param $function Je to funckia ktora ma tvar function(child, args)
     * @param $args pole argumentov
     */
    public static function postOrder ($root, $function, &$args);

    /**!        LOOKUP
     * @brief Staticka globalna funkcia ktora vracia pole najdenych prvkov
     * @param $root Je to root stromu
     * @param $key Kluc podla ktoreho ma vyhladavat
     * @param $var Na ktoru premennu sa mam pozerat
     * @return $out Pole vysledkou
     */
    public static function &lookUp ($root, $key, $var = 'name');


    /**!    CONNECT TAGS
     * @brief Funkcia odstrani end tagy a urci komu je kto otec a syn (poprepaja ich)
     * @return Vsetko sa meni globalne v danej triede
     */
    public function connect ();
}

interface _PARAM
{

    const BIT = 0;                  //!< Dany parameter je: boolean
    const INT = 1;                  //!<                    integer
    const FLOAT = 2;                //!<                    floating point number
    const NVARCHAR = 3;             //!<                    string s pevnou dlzkou
    const NTEXT = 4;                //!<                    string bez obmedzenia

    /**! Type OF string
     * @brief Funkcia rozlisuje co je v stringu (int, bool a pod)
     * @param $str vstupny string
     * @param $text Ci dany string je parameter alebo hodnota tagu ;default:False
     * @return typ v stringovom tvare
     */
    public static function typeOf ($str, $text = false);
}

interface _NODE
{

    /**!    DECODE VALUE
     * @brief Dekoduje $v a $pv na array($value, $type)
     * @param $v value tagu
     * @param $pv value tagu ktory ma parametre
     * @return array($value, $type)
     */

    static function decodeValue ($v, $pv);

    /**!    DECODE PARAMS
     * @brief Dekoduje hodnotu $p na array parametrov
     * @param $p parametre vo tvare stringu
     * @return array( "pname"  => $name,
     *                 "pvalue" => $value,
     *                 "ptype"  => $type)
     */
    static function decodeParams ($p);

    /**!
     *  GET Variable functions:
     */
    public function getParent ();    //!< Vracia parenta

    public function getChilds ();    //!< Vracia pole deti

    public function getParams ();    //!< Vracia pole parametrov

    public function getValue ();     //!< Vracia hodnotu tagu
    /**!
     *  SET Variable functions:
     */
    /**!
     * @brief Prida dieta &$chld do pola deti
     */
    public function setChilds (&$chld);

    /**!
     * @brief Zmeni parenta na noveho &$par
     */
    public function setParent (&$par);

    /**!
     *  STATE functions:
     */
    /**!
     * @brief Zisti ci je tento tag ukoncovaci
     * @return (bool) Ci je koncovy
     */
    public function isEndTag ();
}

interface _LEXIC
{
    const TAG = 1;                  //!< Index nazvu tagu v poli tags
    const VAL = 2;                  //!< Index hodnoty tagu v poli tags
    const PARAM = 3;                //!< Index parametrov zapisancych v tvare <param>="<value>" v poli tags
    const PARNVAL = 4;              //!< Index hodnoty tagu v poli tags (ex.:<tag param="value">parnval</tag>
}

interface _ARG_PARSER
{

    public function parse ();
}

interface _APP
{

    const EXIT_SUCCESS = 0;        //!< normalne ukoncenie programu
    const EXIT_PE = 1;        //!< parameter error
    const EXIT_FNE = 2;        //!< file not exist error
    const EXIT_ACCE = 3;        //!< Access error
    const EXIT_BFE = 4;        //!< zly format
    const EXIT_INTERNAL = 126;      //!< ostatne vnutorne chyby
}

?>
