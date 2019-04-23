<?php

namespace Exakat\Analyzer\Pmb;

use Exakat\Analyzer\Analyzer;

class OpacModifiedParameter extends Analyzer {
    public function dependsOn() {
        return array('Pmb/Parametre',
                    );
    }
    
    public function analyze() {
        $this->analyzerIs('Pmb/Parametre')
             ->is('isModified', true)
             ->goToFile()
             ->regexIs('fullcode', '^/opac_css/')
             ->back('first');
        $this->prepareQuery();
    }
}

?>
