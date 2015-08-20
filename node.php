<?php

#XTD:xcibul10

/**!
 * @file classes.php
 * @brief Su tu triedy popisujuce xml
 * @author Miroslav Cibulka - xcibul10
 */
class node extends param implements _NODE
{

    public $parent; //!< type node
    public $childs = array (); //!< type array(node)
    public $params; //!< type param
    public $name;
    public $value;
    public $ended;      //!< Ci je node ukonceny </node>

    private $end;       //!< (temp) ci je node ukoncovaci tag </node>

    function __construct ($tag, $val, $param, $parnval, $isEnded = false)
    {
        $this->name = $tag;
        $this->ended = $isEnded;
        if ($tag[0] != '/') {
            $this->params = self::decodeParams($param);
            $this->value = self::decodeValue($val, $parnval);
            $this->end = false;
        } else {
            $this->params = null;
            $this->value = null;
            $this->end = true;
        }
    }

    function __toString ()
    {
        $out = "";
        if ($this->params != null)
            foreach ($this->params as $par) {
                $out .= "
                     ((OBJECT) {
                         name => '$par->pname',
                         type =>  $par->ptype,
                        value => '$par->pvalue'
                     }),
                  ";
            };
        if ($this->value != null)
            $values = "
                     'name' => '" . $this->value["name"] . "',
                     'type' => '" . $this->value["type"] . "'
                  ";
        else
            $values = "";
        $childs = "";
        foreach ($this->childs as $child) {
            $childs .= "
                     ((OBJECT) '$child->name'),
                  ";
        }
        $parent = "
                     ";
        if ($this->parent != null)
            $parent = "
        parent  => ((OBJECT) '" . $this->parent->name . "'),";

        return "((OBJECT) {
          name  => '$this->name',
    parameters  => array ( $out ),
         value  => array ( $values ),
     isEndTag?  => " . ($this->isEndTag() ? "True" : "False") . ",$parent
        childs  => array ( $childs )
  })\n\n";
    }

    static function decodeValue ($v, $pv)
    {
        $v = $v == "\n" ? "" : $v;
        $pv = $pv == "\n" ? "" : $pv;
        if ($v . $pv == '') return null;
        return array (
            "name" => $v . $pv,
            "type" => parent::typeOf($v . $pv, true)
        );
    }

    static function decodeParams ($p)
    {
        if (!isset($p) || $p == '') return null;
        $params = "";
        $out = array ();
        if (preg_match_all('/(\S+)=[\'"]([^"\']*)[\'"]/', $p, $params) >= 1) {
            for ($i = 0; $i < count($params[1]); $i++) {
                array_push($out, new param($params[2][$i], parent::typeOf($params[2][$i]), $params[1][$i]));
            }
        } else {
            $obj = new EVENT("Zle zadany parameter ?$p?");
            $obj->error(APP::EXIT_INTERNAL);
        }
        return $out;
    }

    public function setParent (&$par)
    {
        $this->parent = $par;
    }

    public function setParams (&$param)
    {
        if ($this->params != null)
            array_push($this->params, $param);
        else {
            $this->params = array ($param);
        }
    }

    public function setChilds (&$chld)
    {
        array_push($this->childs, $chld);
    }

    public function isEndTag ()
    {
        return $this->end;
    }

    public function getParent ()
    {
        return $this->parent;
    }

    public function getChilds ()
    {
        return $this->childs;
    }

    public function getParams ()
    {
        return $this->params;
    }

    public function getValue ()
    {
        return $this->value;
    }
}

?>
