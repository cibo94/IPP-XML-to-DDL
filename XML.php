<?php

#XTD:xcibul10
/**!
 * @file classes.php
 * @brief Su tu triedy popisujuce xml
 * @author Miroslav Cibulka - xcibul10
 */
class XML implements _XML
{

    protected $nodes = array ();
    private $ddlxml;                                //!< tuto to bude prelozene
    public $style = self::XML;
    public $HEADER = null;                          //!< Header (string)
    public $ETC = -1;                               //!< max pocet rovnakych foreign klucov
    public $G = false;                              //!< XML format s relaciami popisujuci DDL
    public $A = false;                              //!< bez slpcov z atributov
    public $B = false;                              //!< max pocet rovnakych foreign klucov je 1

    public function getTree ()
    {
        if (empty($this->nodes))
            return null;
        else
            return $this->nodes[0];
    }

    public function &convertToDDL ()
    {
        $this->ddlxml = new DDL($this->getTree(),
            $this->HEADER, $this->ETC, $this->A,
            $this->B, $this->G);                    //!< novy strom odkazujuci sa na XML strom z ktoreho vznikne
        $nodes = $this->nodes;
        array_shift($nodes);                        //!< odstranenie prveho nodu (zapuzdrenie tabulky ... zadanie)
        $tables = array ();                          //!< zaznamenava ake tabulky uz boli vytvorene
        $this->ddlxml->tables = new node("tables", "", "", "");
        $late = array ();                            //!< pridavanie child elementov az na konci ked su tabulky spravene
        $existsAsParam = array ();
        $existsAsNode = array ();
        foreach ($nodes as $node) {                 //!< Prehliadava nody postupne v poli
            if (!isset($tables[$node->name])) {     //!< ak uz bol prehliadnuty tak sa preskakuje
                $table = new node($node->name, $node->value["name"], "", "");
                $p = array ();
                foreach (self::lookUp($this->nodes[0], $node->name) as $syn) {
                    if ($table->value != null && param::greater($syn->value["type"], $table->value["type"]))
                        $table->value["type"] = $syn->value["type"];
                    if ($table->value == null && $syn->value != null)
                        $table->value = $syn->value;
                    if (!$this->A)
                        if ($syn->params != null)
                        foreach ($syn->params as $param) {
                            if ($table->value != null && "value" == $param->pname) {
                                if (!param::greater($table->value["type"], $param->ptype))
                                    $table->value["type"] = $param->ptype;
                                continue;
                            }
                            if (isset($existsAsNode[$param->pname]))
                                ($e = new EVENT("Atribut a subelement nemozu mat rovnake meno!") and $e->error(90));
                                if (!isset($p[$param->pname])) {
                                    $table->setParams($param);
                                    $p[$param->pname] = $param;
                                } else {
                                    if (!param::greater($p[$param->pname]->ptype, $param->ptype)) {
                                        $p[$param->pname]->ptype = $param->ptype;
                                    }
                                }
                            $existsAsParam[$param->pname] = $syn->name;
                        }
                }
                $this->ddlxml->tables = $table;
                //!< prida sa do stromu ako tabulka
                //!< od tialto sa spracuvaju childy ...
                $syns = self::lookUp($this->nodes[0], $node->name);
                $col = array ();                     //!< aby sa stlpce neopakovali ...
                foreach ($syns as $syn) {
                    foreach ($syn->childs as $tag) {
                        if (isset($col[$tag->name])) continue;
                        if (isset($existsAsParam[$tag->name . "_id"]))
                            ($e = new EVENT("Atribut a subelement nemozu mat rovnake meno!") and $e->error(90));
                        $cetnost = DDL::maxCetnost($syns, $tag->name);
                        if (isset($this->ETC) && $cetnost > $this->ETC && $tag->name != $syn->name) {
                            if (isset($late[$tag->name]) && !self::isIn($late[$tag->name], $table->name))
                                array_push($late[$tag->name], $table->name);
                            else
                                $late[$tag->name] = array ($table->name);
                        } elseif ($this->B || $cetnost <= 1 || $tag->name == $syn->name) {
                            $this->ddlxml->{$node->name} = new node($tag->name, $tag->value["type"], "", "");
                        } else {
                            for ($i = 1; $i <= $cetnost; $i++) {
                                $this->ddlxml->{$node->name} = new node($tag->name, "", "i='$i'", $tag->value["type"]);
                            }
                        }
                        $col[$tag->name] = true;
                        $existsAsNode[$tag->name] = true;
                    }
                }
                $tables[$node->name] = true;        //!< tabulka je spracovana
            }
        }
        foreach ($nodes as $node) {
            if (isset($late[$node->name])) {
                foreach ($late[$node->name] as $n) {
                    $this->ddlxml->{$node->name} = new node($n, "", "", "");
                }
                unset($late[$node->name]);
            }
        }
        //    $this->ddlxml->style = self::OBJECT;
        return $this->ddlxml;
    }


    public static function preOrder ($root, $function, &$args)
    {
        if (!isset($root)) return;
        $function($root, $args);
        foreach ($root->childs as $child) {
            self::preOrder($child, $function, $args);
        }
    }

    public static function postOrder ($root, $function, &$args)
    {
        if (!isset($root)) return;          //!< vzdy ked root neexistuje nastane
        //!< navrat z rekurzie
        foreach ($root->childs as $child) {  //!< prechadzame prvok po prvku
            self::postOrder($child, $function, $args);
        }                                   //!< PostOrder ==> dana funkcia je volana na konci
        $function($root, $args);
    }

    public static function &lookUp ($root, $key, $var = 'name')
    {
        $out = array ();                     //!< vytvorenie vystupneho pola
        if (!isset($root)) return null;     //!< Kontrola existencie pola
        if ($root->$var == $key) {          //!< ak je hladane prvok koren
            $out = array (&$root);            //!< tak sa prida do pola najdenych
        }
        if ($root->childs != null)          //!< ak nema deti vrati pole najdenych
            foreach ($root->childs as $child) {
                //!< inak sa prehladavaju jednotlive deti
                $out = array_merge($out, self::lookUp($child, $key, $var));
            }
        return $out;
    }

    public function connect ()
    {
        $STACK = array ();                   //!< bolo treba STACK na uchovanie otcov
        $parent = null;                     //!< pomocna premenna na uchovanie popnuteho otca
        $max = count($this->nodes);         //!< pocet prvkov pola (pole sa zmensuje => odstranuju sa EndTagy
        for ($i = 0; $i < $max; $i++) {
            if (empty($STACK)) {            //!< ak je stack prazdny tak sa pushne prvy tag
                array_push($STACK, $this->nodes[$i]);
            } else {                        //!< inak popne parenta a ...
                $parent = array_pop($STACK);
                if ($this->nodes[$i]->ended) {
                    $this->nodes[$i]->setParent($parent);
                    $parent->setChilds($this->nodes[$i]);
                    array_push($STACK, $parent);
                } elseif ($this->nodes[$i]->isEndTag() == false) {
                    //!< rozhodne ci je to endTag ak nie tak ...
                    $this->nodes[$i]->setParent($parent);
                    $parent->setChilds($this->nodes[$i]);
                    array_push($STACK, $parent);
                    array_push($STACK, $this->nodes[$i]);
                    //!< nastavi parenta na dany prvok a parentovy prida dieta
                } else {                    //!< prvok je endTag tak sa zmaze => je prebytocny
                    array_splice($this->nodes, $i, 1);
                    $max--;
                    $i--;
                }
            }
        }
    }

    function __construct ($array)
    {
        {
            $obj = new EVENT("XML parser loaded!");
            $obj->log();
        }
        $this->nodes = $array;
    }

    function __destruct ()
    {
        {
            $obj = new EVENT("XML parser destroyed!");
            $obj->log();
        }
    }

    /**!
     * @brief vykreslenie XML stromu pri vypise
     */
    function __toString ()
    {
        if ($this->style == self::OBJECT) {
            $out = "class XML {\n";
            self::preOrder($this->getTree(), (function ($node, &$out) {
                $out .= "  " . $node;
            }), $out);
            return $out . "}\n";
        } else {
            return $this->printXML($this->getTree());
        }
    }

    /**!    __GET Magic Function
     * @brief Pri pristupovani na (neexistujuci) clen triedy vrati najdeny tag zo stromu
     * @param $name Nazov tagu, ktory chceme ziskat
     * @return vracia pole tagov
     */
    function &__get ($name)
    {
        return self::lookUp($this->nodes[0], $name, "name");
    }

    /**!    __SET Magic Function
     * @brief Pridava prvky za 'nastavovany' prvok
     * @param $name Nazov tagu do ktoreho chceme ulozit noveho potomka
     * @param $value Tag == potomok
     * @return $nodes Zoznam otcou
     */
    function __set ($name, $value)
    {
        if (empty($this->nodes)) {
            $this->nodes = array ($value);     //!< Tato hodnota vytvori strom XML
            return;                             //!< ukoncime
        }
        $i = 0;                                 //!< kedze strom nie je prazdny ideme pridavat
        for (; $i < count($this->nodes); $i++) {
            if ($this->nodes[$i]->name == $name) {
                break;
            }
        }
        if ($i == count($this->nodes))
            ($e = new EVENT("Internal Error!") and $e->error(APP::EXIT_INTERNAL));
        //!< Najprv najdeme prvok za ktory chceme pridavat hodnotu
        $value->parent = $this->nodes[$i];      //!< nastavenie otca na prvy najdeny prvok
        //!<        pretoze rekurzia obracia poradie prvokov v poli
        self::array_insert($this->nodes, $i + 1, $value);
        $this->nodes[$i]->setChilds($value);
        //!< a pridanie naseho nodu do childov otca
    }

    /**
     * @param array $array
     * @param int|string $position
     * @param mixed $insert
     */
    static function array_insert (&$original, $position, $insert)
    {
        $insert = array ($insert);
        return array_splice($original, $position, 0, $insert);
    }

    /**!    Is in ARRAY
     * @brief pomocna funkcia k convertToDDL() ktora zisti ci je daco v poli
     * @param $node pole typu node, v ktorom hladame
     * @param $child hladany prvok
     * @return true ak sa nasiel prvok
     */
    private function isIn ($node, $child)
    {
        foreach ($node as $n) {
            if ($n == $child) {
                return true;
            }
        }
        return false;
    }

    /**!        XML Print
     * @brief vypise v jazyku XML
     * @param $root vstupny strom
     * @return String XMLka
     */
    private function printXML ($root)
    {
        if (!isset($root)) return;
        $out = "<$root->name";
        if ($root->params != null)
            foreach ($root->params as $param) {
                $out .= " " . $param->pname . "='" . $param->pvalue . "'";
            }
        if ($root->value != null || $root->childs != null)
            $out .= ">";
        if ($root->value != null && $root->childs == null)
            $out .= $root->value["name"];
        elseif ($root->childs != null) {
            $out .= "\n";
            foreach ($root->childs as $child) {
                $out .= $this->printXML($child);
            }
        }
        if ($root->value == null && $root->childs == null)
            return $out . " />\n";
        else
            return $out . "</$root->name>\n";
    }
}

?>
