<?php

namespace jas\debug;

use jas\debug\Dump\Display;

class Dumper {
    const OPT_OUTPUT = 0;
    const OPT_RETURN = 1;
    
    const DISPLAY_TEXT = 4;
    const DISPLAY_PRE = 8;
    const DISPLAY_HTML = 16;
    
    protected $dumper_cache = array();
    protected static $object_cache = array(); // only stores object ids, so no need to be gc'ed
    protected $recursive_cache = array();
    
    public static $defaultSettings = array(
        'max_nesting_deep' => 2,
        'indent_spaces' => 4,
        'background' => false,
    );
    
    public static $primitiveType = 'jas\debug\Dump\Primitive';
    public static $registeredTypes = array(
        //'\SugarBean' => 'jas\debug\Dump\SugarBean',
        //'\Varien_Object' => 'jas\debug\Dump\Varien_Object',
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
        if (is_object($var) || is_array($var)) {
            if (in_array($var, $this->recursive_cache, true)) {
                $r = $type->getRecursion($obj);
            } else {
                $this->recursive_cache[] =& $var;
            }
        }
        if ($r === null)
            $r = $type->get($var);
        $this->level--;
        return $r;
    }
    protected function getType($type, &$obj) {
        if (!isset($this->dumper_cache[$type]))
            $this->dumper_cache[$type] = new $type($this);
        return $this->dumper_cache[$type];
    }
    
    /**
     * Alterantive zu {@link var_dump()} mit einigen Optimiereung.
     *
     *  - Für SugarBean-Objekte werden nur die Attribute die nach field_defs definiert sind ausgegeben.
     *  - Implementation von {@link Dumpable} werden wie das entsprechende zurÃ¼ckgegebene Elemnt ausgegeben (array, string, int, etc...)
     *  - Ein optionaler Parameter am Ende erlaubt folgende modifikationen: {@link $level}
     *
     * Konfigurations-Parameter in {@link $config}['utils']['dump']:
     *  - max_nested_objects: Anzahl an Ebenen von Objekten die dargestellt werden sollen (Standard 1; -1 fÃ¼r Alle)
     * @param mixed $var ,... Ein oder mehrere auszugebende Variablen
     * @param int $level optional -1: ZurÃ¼ck geben statt Ausgeben; -2 fÃ¼r Ausgeben mit <<pre>> (fÃ¼r HTML-Darstellung)
     * @return NULL|string Falls level -1 wird die Formatierte Ausgabe zurÃ¼ck gegeben
     */
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
        $r = call_user_func_array(array($dumper, 'dumps'), $args);
        echo "<hr />";var_dump($dumper);echo "<hr />";
        return $r;
        /*
        if (count($args) > 2 || count($args) == 2 && !is_int($level)) {
            $level = 0;
            if (count($args) > 2 && is_int($level = end($args)) && $level < 0) {
                $level = array_pop($args);
            }
            reset($args);
            $ret = "";
            while (list($i, $bean) = each($args)) {
                if (!empty($ret))
                    $ret .= "\n";
                $ret .= dump($bean, $level);
            }
            return $ret;
        } else {
            static $reco = array(); // Rekursions-PrÃ¼fung: Verhindern von Rekursiv dargestellten Objekten
            if (($l = max(0, $level)) == 0) { // Level 0: zurÃ¼cksetzen des Rekursions-Caches
                $reco = array();
            }
            
            if (is_object($bean) && is_a($bean, "SugarBean")) {
                $valid_attr = array(
                    'id',
                    //'new_schema',
                    'new_with_id',
                    //'processed_dates_times', 'process_save_dates', 'number_formatting_done',
                    //'save_from_post', 'duplicates_found',
                    'table_name', 'object_name', 'module_dir',
                    'update_date_modified', 'update_modified_by', 'update_date_entered', 'set_created_by',
                    'deleted',
                );
                foreach (array_keys($bean->field_defs) as $key) {
                    if (!in_array($key, $valid_attr)) {
                        $valid_attr[] = $key;
                    }
                }
                if (isset($bean->custom_fields) && isset($bean->custom_fields->avail_fields)) {
                    foreach (array_keys($bean->custom_fields->avail_fields) as $key) {
                        if (!in_array($key, $valid_attr)) {
                            $valid_attr[] = $key;
                        }
                    }
                }
                $string = "";
                $fields = 0;
                foreach ($valid_attr as $key) {
                    if (property_exists($bean, $key) && !isset($bean->$key)) {
                        $fields++;
                        $string .= '  ["'.$key.'"] => null'."\n";
                    } elseif (isset($bean->$key) && (!is_object($bean->$key) || $bean->$key instanceof Dumpable)) {
                        $fields++;
                        $string .= '  ["'.$key.'"] => ';
                        $x = dump($bean->$key, -1);
                        $string .= trim(str_replace("\n", "\n  ", trim($x)))."\n";
                    }
                }
                $string  = "SugarBean(".get_class($bean).") (".$fields.") {\n" . $string;
                $string .= "}\n";
            } elseif (is_object($bean)) {
                static $oids = array(); // Objekt Indexes: Index des Array ist die ID des Objektes
            
                if (($id = array_search(spl_object_hash($bean), $oids)) === false) {
                    $oids[] = spl_object_hash($bean);
                    $id = array_search(spl_object_hash($bean), $oids);
                }
                $max_level = Config::get('utils.dump.max_nested_objects', 1);
                if (in_array(spl_object_hash($bean), $reco)) {
                    $string = "&object(".get_class($bean).")#$id (*rekursion*)\n";
                } else {
                    $reco[] = spl_object_hash($bean);
                    if ($bean instanceof interfaces\Dumpable) {
                        $class = get_class($bean);
                        $string = "object(".$class.")#$id";
                        $props = $bean->_toDump();
                        if (is_array($props)) {
                            $string .= " (".count($props).") {\n";
                            foreach ($props as $key => $prop) {
                                $string .= "  [".(is_string($key) ? "\"{$key}\"" : $key)."] => ";
                                $string .= ltrim(dump($prop, $l+1));
                            }
                            $string .= "}\n";
                        } else {
                            $string .= " ".dump($props, $l+1);
                        }
                    } elseif ($l < $max_level || $max_level == -1) {
                        $class = get_class($bean);
                        $string = "object(".$class.")#$id";
                        $rfl = new \ReflectionObject($bean);
                        $string .= " (".count($props = $rfl->getProperties()).") {\n";
                        foreach ($props as $prop) {
                            if ($prop->isStatic())
                                continue;
                            $prop->setAccessible(true);
                            $key = $prop->getName();
                            $pclass = $prop->class == $class ? '' : ':"'.$class.'"';
                            $visible = $prop->isProtected() ? ':protected' :
                            $prop->isPrivate() ? ':private' : '';
                            
                            $string .= "  [".(is_string($key) ? "\"{$key}\"" : $key).$pclass.$visible."] => ";
                            $string .= ltrim(dump($prop->getValue($bean), $l+1));
                        }
                        $string .= "}\n";
                    } else {
                        $string = "&object(".get_class($bean).")#$id {...}\n";
                    }
                }
            } elseif (is_array($bean)) {
                $string = "array(".count($bean).")";
                if (count($bean) != 0) {
                    $string .= " {\n";
                    reset($bean);
                    while (list($key, $val) = each($bean)) {
                        $string .= "  [".(is_string($key) ? "\"{$key}\"" : $key)."] => ";
                        $string .= ltrim(dump($val, max(0, $level) + 1));
                    }
                    $string .= "}\n";
                } else {
                    $string .= "\n";
                }
            } elseif (is_int($bean)) {
                $string = "int(".$bean.")\n";
            } elseif (is_float($bean)) {
                $string = "float(".$bean.")\n";
            } elseif (is_bool($bean)) {
                $string = "bool(".($bean ? 'true' : 'false').")\n";
            } elseif (is_string($bean)) {
                $string = "string(".strlen($bean).") \"{$bean}\"\n";
            } elseif (is_null($bean)) {
                $string = "NULL\n";
            } elseif (is_resource($bean)) {
                $string = "resource(".(int)$bean.") of type (".get_resource_type($bean).")\n";
            } else {
                $string = "unknown_type(".gettype($bean).") ".(string)$bean."\n";
            }
            if ($level == 0) {
                echo $string;
            } elseif ($level == -1) {
                return $string;
            } elseif ($level == -2) {
                echo "<pre>".$string."</pre>";
            } else {
                return '  '.rtrim(str_replace("\n", "\n  ", $string), ' ');
                //return str_repeat('  ', $level) . rtrim(str_replace("\n", "\n".str_repeat('  ', $level), $string), ' ');
            }
        }*/
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