<?php

namespace jas\debug;

use jas\debug\Dump\Display;

/**
 * Alterantive zu {@link var_dump()} mit einigen Optimiereung.
 *
 *  - Für SugarBean-Objekte werden nur die Attribute die nach field_defs definiert sind ausgegeben.
 *  - Implementation von {@link Dumpable} werden wie das entsprechende zurückgegebene Elemnt ausgegeben (array, string, int, etc...)
 *  - Ein optionaler Parameter am Ende erlaubt folgende modifikationen: {@link $level}
 *
 * @param mixed $var ,... Ein oder mehrere auszugebende Variablen
 * @param int $level optional -1: ZurÃ¼ck geben statt Ausgeben; -2 fÃ¼r Ausgeben mit <<pre>> (fÃ¼r HTML-Darstellung)
 * @return NULL|string Falls level -1 wird die Formatierte Ausgabe zurÃ¼ck gegeben
 */
class Dumper {
    const OPT_OUTPUT = 0;
    const OPT_RETURN = 1;
    
    const DISPLAY_TEXT = 4;
    const DISPLAY_PRE = 8;
    const DISPLAY_HTML = 16;
    
    protected $dumper_cache = array();
    protected static $object_cache = array(); // only stores object ids, so no need to be gc'ed
    protected $recursive_cache = array();
    /**
     * Konfigurations-Parameter:
     *  - max_nesting_deep: Anzahl an Ebenen von Objekten die dargestellt werden sollen (Standard 1; -1 für Alle)
     * @var array
     */
    public static $defaultSettings = array(
        'max_nesting_deep' => 2,
        'indent_spaces' => 4,
        'background' => false,
        'huge_array_el_count' => 3,
    );
    
    public static $primitiveType = 'jas\debug\Dump\Primitive';
    public static $registeredTypes = array(
        '\SugarBean' => 'jas\debug\Dump\SugarBean',
        '\Varien_Object' => 'jas\debug\Dump\Varien_Object',
    );
    
    protected $options = 0;
    protected $settings;
    protected $level = 0;
    
    /**
     * @param int $options OPT_OUTPUT | DISPLAY_TEXT
     */
    public function __construct($options = 0, array $settings = array()) {
        if (!($options & 28)) // no display options set -> set default display
            $options |= self::DISPLAY_HTML;
        $this->options = $options;
        $this->settings = array_merge(static::$defaultSettings, $settings);
    }
    
    public function getMaxLevel() {
        return $this->settings['max_nesting_deep'];
    }
    public function getCurrentLevel() {
        return $this->level;
    }
    
    public function dumps($var/*, ... */) {
        $ret = null;
        foreach (func_get_args() as $arg) {
            $ret .= $this->_dump($arg);
        }
        return $ret;
    }
    
    protected function _dump(&$var) {
        $this->recursive_cache = array();
        $r = null;
        
        if ($this->options & self::DISPLAY_HTML) {
            $r .= Display::getStyleSheet();
            if (is_bool($this->settings['background']) && $this->settings['background'])
                $r .= '<div class="'.Display::CSS_NS.'dump" style="background: white;">';
            elseif ($this->settings['background'])
                $r .= '<div class="'.Display::CSS_NS.'dump" style="background: '.$this->settings['background'].';">';
            else
                $r .= '<div class="'.Display::CSS_NS.'dump">';
        }
        elseif ($this->options & self::DISPLAY_PRE)
            $r .= "<pre>";
        
        $r .= $this->d($var);
        
        if ($this->options & self::DISPLAY_HTML)
            $r .= "</div>";
        elseif ($this->options & self::DISPLAY_PRE)
            $r .= "</pre>";
        
        $this->recursive_cache = array();
        if ($this->options ^ self::OPT_OUTPUT)
            echo $r;
        else
            return $r;
    }
    
    public function d(&$var) {
        $this->level++;
        $dt = static::$primitiveType;
        if (is_object($var)) {
            foreach (static::$registeredTypes as $c => $t) {
                if (is_a($var, $c)) {
                    $dt = $t;
                    break;
                }
            }
        }
        $type = $this->getType($dt);
        
        $r = null;
        if (is_object($var) || (is_array($var) && count($var) >= $this->settings['huge_array_el_count'])) {
            if (in_array($var, $this->recursive_cache, true)) {
                $r = $type->getRecursion($var);
            } else {
                $this->recursive_cache[] =& $var;
            }
        }
        if ($r === null) {
            if ($this->getMaxLevel() > -1 && $this->getCurrentLevel() > $this->getMaxLevel())
                $r = $type->getPrototype($var);
            else
                $r = $type->get($var);
        }
        $this->level--;
        return $r;
    }
    protected function getType($type, &$obj) {
        if (!isset($this->dumper_cache[$type]))
            $this->dumper_cache[$type] = new $type($this);
        return $this->dumper_cache[$type];
    }
    
    public static function dump($bean, $opt = 0) {
        $args = func_get_args();
        if (count($args) > 1) {
            if (is_int(end($args))) {
                $opt = array_pop($args);
            } else {
                $opt = 0;
            }
        }
        if ($opt === -1)
            $opt = self::OPT_RETURN;
        elseif ($opt === -2)
            $opt = self::DISPLAY_PRE | self::OPT_OUTPUT;
        
        $dumper = new static($opt);
        return call_user_func_array(array($dumper, 'dumps'), $args);
    }
    
    public static function objUniqId(&$val) {
        return md5(get_class($val) . "\0" . spl_object_hash($val));
    }
    public static function getObjectId(&$val) {
        $id = self::objUniqId($val);
        if (!in_array($id, self::$object_cache))
            self::$object_cache[] = $id;
        return array_search($id, self::$object_cache);
    }
    
    public function et($c, $val) {
        if ($this->options & self::DISPLAY_HTML)
            $val = '<span class="'.Display::CSS_NS.$c.'">'.$val.'</span>';
        
        return $val;
    }
    public function er($c, $val) {
        if ($this->options & self::DISPLAY_HTML)
            $val = '<span class="'.Display::CSS_NS.'type-'.$c.'">'.$val.'</span><br />'."\n";
        else
            $val .= "\n";
        return $val;
    }
    public function nl() {
        if ($this->options & self::DISPLAY_HTML)
            return "<br />\n";
        else
            return "\n";
    }
    public function indent($s) {
        $i = str_repeat(' ', $this->settings['indent_spaces']);
        if ($this->options & self::DISPLAY_HTML)
            $i = '<span class="'.Display::CSS_NS.'indent">'.$i.'</span>';
        return preg_replace('/^/m', $i, $s);
    }
}