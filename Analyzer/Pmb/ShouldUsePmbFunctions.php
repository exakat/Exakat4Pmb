<?php

namespace Exakat\Analyzer\Pmb;

use Exakat\Analyzer\Analyzer;

class ShouldUsePmbFunctions extends Analyzer {
    public function analyze() {
        $pmb = $this->loadIni('pmb.ini', 'misc');
        $pmb = makeFullNsPath($pmb);

        // $a = str_replace();
        $this->atomIs('Functioncall')
             ->fullnspathIs($pmb);
        $this->prepareQuery();
    }
}

?>
