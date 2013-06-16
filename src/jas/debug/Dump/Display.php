<?php

namespace jas\debug\Dump;

class Display {
    const CSS_NS = 'jas-debug-';
    
    const TYPE = 'type';
    const SUB_TYPE = 'subtype';
    const TYPE_ATTR_VAL = 'typeattrval';
    const TYPE_CONT_VAL = 'typecontentval';
    const ATTR_KEY = 'attrkey';
    const ATTR_KEY_VAL = 'attrkeyval';
    const ATTR_ASSIGN = 'attrassign';
    const VAL_QUOTES = 'valquotes';
    const REFERENCE = 'ref';
    const KEY_CLASS = 'keyclass';
    const KEY_VISIBILITY = 'keyvisibility';
    const COLLAPSED = 'collapsed';
    const PLACEHOLDER = 'placeholder';
    
    public static function getStyleSheet() {
        $ns = self::CSS_NS;
        $css = <<<EOS
<style type="text/css">
.{$ns}dump {
    font-family: monospace;
    font-size: 12px;
}
.{$ns}type {
    font-weight: bold;
}
.{$ns}attrkeyval {
    color: #c70;
}
.{$ns}keyclass, .{$ns}keyvisibility {
    font-size: 0.9em;
    font-family: Sans-serif;
    font-style: italic;
}
.{$ns}type-int .{$ns}typecontentval,
.{$ns}type-float .{$ns}typecontentval {
    color: lightgreen;
}
.{$ns}type-string > .{$ns}typecontentval {
    color: #900;
}
.{$ns}type-bool > .{$ns}typeattrval > .{$ns}typecontentval {
    color: #905;
    font-weight: bold;
}
.{$ns}type-object > .{$ns}typeattrval > .{$ns}typecontentval {
    color: darkgray;
}
.{$ns}keyclass {
    color: darkgray;
}
.{$ns}attrassign {
    color: #222;
}
.{$ns}indent {
    white-space: pre;
}
.{$ns}type-null {
    font-weight: bold;
    font-style: italic;
    color: #000033;
    text-transform: lowercase;
}
</style>
EOS;
        return preg_replace("/\s+/", " ", str_replace("\n", " ", trim($css)));
    }
}