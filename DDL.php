<?php

#XTD:xcibul10
class DDL extends XML implements _DDL
{

    private $XML;                //!< typ  (class) XML
    public $HEADER = null;       //!< Header (string)
    public $ETC = -1;            //!< max pocet rovnakych foreign klucov
    public $G = false;           //!< XML format s relaciami popisujuci DDL
    public $A = false;           //!< bez slpcov z atributov
    public $B = false;           //!< max pocet rovnakych foreign klucov je 1
    /**!
     * Relations between [$parent] and [$node]
     * 0 (self::E)   - No relations
     * 1 (self::NTO) - N:1 relation
     * 2 (self::OTN) - 1:N relation
     * 3 (self::NTM) - N:M relation
     * 4 (self::OTO) - 1:1 relation
     */
    public $RELATIONS;           //!< Public relations


    /**!
     * @brief DDL Contructor
     * @param $legacy XML file from which DDL have been created
     * @param $header Header string inserted to DDL output
     * @param $etc Max number of the same foreign keys
     * @param $a No attribute collumn
     * @param $b If more foreign key exists they are shown as one
     * @param $g Print XML relations between tables
     */
    function __construct ($legacy, $header, $etc, $a, $b, $g)
    {
        {
            $obj = new EVENT("DDL parser loaded!");
            $obj->log();
        }
        $this->XML = $legacy;
        $this->HEADER = $header;
        $this->ETC = $etc;
        $this->A = $a;
        $this->B = $b;
        $this->G = $g;
    }

    function __destruct ()
    {
        {
            $obj = new EVENT("DDL parser destroyed!");
            $obj->log();
        }
    }

    function __toString ()
    {
        if ($this->style == self::OBJECT) {
            $out = "DDL class\n";
            foreach ($this->getTree()->childs as $node) {
                $out .= "| |-+>" . $node->name . "\t Value: " . $node->value['type'] . "\t Attributes: ";
                foreach ($node->params as $param) {
                    $out .= $param->pname . "='" . $param->ptype . "' ";
                }
                $out .= "\n";
                foreach ($node->childs as $ch) {
                    $out .= "| | |-+>>" . $ch->name . "\t >>>> " . $ch->parent->name . "\t Value: " . $ch->value['name'] . "\t Attr: ";
                    foreach ($ch->params as $param) {
                        $out .= $param->pname . "='" . $param->pvalue . "' ";
                    }
                    $out .= "\n";
                }
            }
            return $out . "}\n";
        } else {
            if ($this->G)
                return $this->printXML($this->getTree());
            else
                return $this->printDDL($this->getTree());
        }
    }

    /**!    Print XML
     * @brief Vrati string v XML forme znazornujuce relacie
     * @param $root strom
     * @return string s XML
     */
    private function printXML ($root)
    {
        if (!isset($root)) return "";
        $out = "<?xml version=\"1.0\" encoding=\"UTF-8\"?" . ">";
        $out .= "\n<tables>";
        $par = array ();
        $nodes = $this->nodes;
        array_shift($nodes);
        $this->getRelations();
        foreach ($root->childs as $tag) {
            if (isset($par[$tag->name])) continue;
            $out .= "\n    <table name=\"$tag->name\">";
            $chi = array ();
            foreach ($nodes as $ch) {
                if (isset($chi[$ch->name])) continue;
                $arr = array (0 => null, 1 => "1:N", 2 => "N:1", 3 => "N:M", 4 => "1:1");
                if ($arr[$this->RELATIONS[$tag->name][$ch->name]] != null)
                    $out .= "\n        <relation to=\"$ch->name\" relation_type=\"" . $arr[$this->RELATIONS[$tag->name][$ch->name]] . "\" />";
                $chi[$ch->name] = true;
            }
            $out .= "\n    </table>";
            $par[$tag->name] = true;
        }
        return $out . "\n</tables>\n";
    }

    /**!    Print DDL
     * @brief Vrati string s DDL prikazmi generujuce tabulku
     * @param $root strom z ktoreho sa generuju prikazy
     * @return string s prikazmi
     */
    private function printDDL ($root)
    {
        if (!isset($root)) return "";               //!< ak prisla blbost vrati nic
        if (isset($this->HEADER))
            $out = "--" . $this->HEADER . "\n\n";
        else
            $out = "";
        $tables = array ();
        foreach ($this->getTree()->childs as $node) {
            if (isset($tables[$node->name])) continue;
            $out .= "CREATE TABLE " . $node->name . "(";
            $out .= "\n   prk_" . $node->name . "_id INT PRIMARY KEY";
            $ch = array ();
            foreach ($node->childs as $child) {
                if ($child->params) {
                    if (isset($ch[$child->name . $child->params[0]->pvalue])) continue;
                    $out .= ",\n   " . $child->name . $child->params[0]->pvalue . "_id INT";
                    $ch[$child->name . $child->params[0]->pvalue] = true;
                } else {
                    if (isset($ch[$child->name])) continue;
                    $out .= ",\n   " . $child->name . "_id INT";
                    $ch[$child->name] = true;
                }
            }
            if ($node->params != null)
                foreach ($node->params as $param)
                    $out .= ",\n   $param->pname $param->ptype";
            if ($node->value != null)
                $out .= ",\n   value " . $node->value['type'];
            $out .= "\n);\n\n";                          //!< uzatvorenie tabulky
            $tables[$node->name] = true;
        }
        return $out;
    }

    /**!       Get RELATIONS between tags
     * @brief Najde relacie medzi $node a $parent bez vyuzitia trazitivity
     * @return null ak (netranzitivny) vztah neexistuje inak array('N' => ?, 'M' => ?)
     */
    private function getRelations ()
    {
        foreach ($this->getTree()->childs as $a) {
            foreach ($this->getTree()->childs as $b) {
                if (!isset($this->RELATIONS[$a->name][$b->name]))
                    if ($a->name == $b->name)
                        $this->RELATIONS[$a->name][$b->name] = self::OTO;
                    elseif ((count(self::lookUp($b, $a->name)) > 0) &&
                        (count(self::lookUp($a, $b->name)) > 0)
                    )
                        $this->RELATIONS[$a->name][$b->name] = self::NTM;
                    elseif (count(self::lookUp($b, $a->name)) > 0)
                        $this->RELATIONS[$a->name][$b->name] = self::NTO;
                    elseif (count(self::lookUp($a, $b->name)) > 0)
                        $this->RELATIONS[$a->name][$b->name] = self::OTN;
                    else
                        $this->RELATIONS[$a->name][$b->name] = self::E;
            }
        }
        foreach ($this->getTree()->childs as $a)
            foreach ($this->getTree()->childs as $b)
                if ($this->RELATIONS[$a->name][$b->name] == self::E)
                    foreach ($this->getTree()->childs as $c)
                        if ($a->name != $c->name &&
                            $b->name != $c->name &&
                            $a->name != $b->name
                        )
                            if (($this->RELATIONS[$a->name][$c->name] == self::OTN) &&
                                ($this->RELATIONS[$c->name][$b->name] == self::OTN)
                            )
                                $this->RELATIONS[$a->name][$b->name] = self::OTN;

        foreach ($this->getTree()->childs as $a)
            foreach ($this->getTree()->childs as $b)
                if ($this->RELATIONS[$a->name][$b->name] == self::E)
                    foreach ($this->getTree()->childs as $c)
                        if ($a->name != $c->name &&
                            $b->name != $c->name &&
                            $a->name != $b->name
                        )
                            if (($this->RELATIONS[$a->name][$c->name] == self::NTO) &&
                                ($this->RELATIONS[$c->name][$b->name] == self::NTO)
                            )
                                $this->RELATIONS[$a->name][$b->name] = self::NTO;

        foreach ($this->getTree()->childs as $a)
            foreach ($this->getTree()->childs as $b)
                if ($this->RELATIONS[$a->name][$b->name] == self::E)
                    foreach ($this->getTree()->childs as $c)
                        if ($a->name != $c->name &&
                            $b->name != $c->name &&
                            $a->name != $b->name
                        )
                            if (($this->RELATIONS[$a->name][$c->name] != self::E) &&
                                ($this->RELATIONS[$c->name][$b->name] != self::E)
                            )
                                $this->RELATIONS[$a->name][$b->name] =
                                $this->RELATIONS[$b->name][$a->name] = self::NTM;
    }

    public static function maxCetnost ($tree, $node)
    {
        $ParSyns = $tree;                   //!< najde vsetky synonyma
        $max = 0;
        foreach ($ParSyns as $syn) {
            $args = array ();
            self::preOrder($syn, (function ($ele, &$args) {
                if (isset($args[$ele->name]))
                    $args[$ele->name]++;
                else
                    $args[$ele->name] = 1;
            }), $args);
            if (isset($args[$node]) && $max < $args[$node])
                $max = $args[$node];
        }
        return $max;
    }
}

?>
