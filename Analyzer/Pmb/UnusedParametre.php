<?php

namespace Exakat\Analyzer\Pmb;

use Exakat\Analyzer\Analyzer;

class UnusedParametre extends Analyzer {
    public function dependsOn() {
        return array('Pmb/Parametre');
    }
    
    public function analyze() {
        $this->analyzerIs('Pmb/Parametre')
             ->values('fullcode');
        $res = $this->rawQuery();
//        print_r($res->toArray());
        
        $parameter = $this->loadIni('pmb_parameters.ini', 'parameters');
        
        $diff = array_diff($parameter, $usedParameter);
//        print_r($parameter);
        
        return;
    }
}

?>
