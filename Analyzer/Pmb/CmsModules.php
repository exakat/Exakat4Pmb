<?php

namespace Exakat\Analyzer\Pmb;

use Exakat\Analyzer\Analyzer;
use Exakat\Data\Dictionary;

class CmsModules extends Analyzer {
    public function analyze() {
        // as a variable
        $this->atomIs('Class')
             ->outIs('NAME')
             ->regexIs('fullcode', '^cms_module_.*')
             ->inIs('NAME')
             ->raw(<<<GREMLIN
     sideEffect{ ppp = []; methods = [:]; constants = [];}
    .where( __.out("NAME").sideEffect{ name = it.get().property("fullcode").value();}.fold())
    .where( __.out("PPP").has('ctype1').out("PPP").sideEffect{ ppp.add(it.get().property("ctype1").value());}.fold())
    .where( __.out("METHOD", "MAGICMETHOD").has('ctype1').sideEffect{ methods[it.get().property("lccode").value()] = it.get().property("ctype1").value();}.fold())
    .where( __.out("CONST").out("CONST").out('NAME').sideEffect{ constants.add(it.get().property("lccode").value());}.fold())

.map{ ["name":name, "ppp":ppp, "methods":methods, "constants":constants]; }
GREMLIN
);
        $res = $this->rawQuery();

        $names = array();
        $hash = array();
        $dico = array();
        foreach($res->toArray() as $c) {
            $dico[$c['name']][] = $c;
        }
        $doubles = array_filter($dico, function ($x) { return count($x) === 2; });

        // find mismatched based on method structure ctype1
        $mismatch = array_filter($doubles, function ($x) { 
            sort($x[0]['methods']);
            sort($x[1]['methods']);
            return join('-', array_values($x[0]['methods'])) === join('-', array_values($x[1]['methods'])); }
        );
        
        $classes = array_keys($mismatch);

        // as an array
        $this->atomIs('Class')
             ->outIs('NAME')
             ->regexIs('fullcode', '^cms_module_.*')
             ->fullcodeIs($classes);
        $this->prepareQuery();
    }
}

?>
