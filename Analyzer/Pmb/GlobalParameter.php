<?php

namespace Exakat\Analyzer\Pmb;

use Exakat\Analyzer\Analyzer;
use Exakat\Data\Dictionary;

class GlobalParameter extends Analyzer {
    public function analyze() {
        $parameters = $this->loadIni('pmb_parameters.ini', 'parameters');
        $parameters = $this->dictCode->translate($parameters, self::CASE_INSENSITIVE);

        $this->atomIs('Array')
             ->has('globalvar')
             ->is('globalvar', $parameters);
        $this->prepareQuery();

        $this->atomIs(self::$VARIABLES_USER)
             ->codeIs($parameters, Analyzer::NO_TRANSLATE);
        $this->prepareQuery();
    }
}

?>
