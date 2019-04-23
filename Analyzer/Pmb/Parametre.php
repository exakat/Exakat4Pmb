<?php

namespace Exakat\Analyzer\Pmb;

use Exakat\Analyzer\Analyzer;

class Parametre extends Analyzer {
    public function analyze() {
        // $opac_biblio_website
        $parameter = $this->loadIni('pmb_parameters.ini', 'parameters');

        $this->atomIs(self::$VARIABLES_USER)
             ->fullcodeIs($parameter, self::TRANSLATE, self::CASE_SENSITIVE)
             ->back('first');
        $this->prepareQuery();
    }
}

?>
