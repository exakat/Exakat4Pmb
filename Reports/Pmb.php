<?php
/*
 * Copyright 2012-2019 Damien Seguy – Exakat SAS <contact(at)exakat.io>
 * This file is part of Exakat.
 *
 * Exakat is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Exakat is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with Exakat.  If not, see <http://www.gnu.org/licenses/>.
 *
 * The latest code can be found at <http://exakat.io/>.
 *
*/

namespace Exakat\Reports;

use Exakat\Analyzer\Analyzer;
use Exakat\Data\Methods;
use Exakat\Exakat;
use Exakat\Phpexec;
use Exakat\Reports\Reports;
use Exakat\Reports\Helpers\Results;

class Pmb extends Ambassador {
    const FILE_FILENAME  = 'pmb';
    const FILE_EXTENSION = '';

    public function dependsOnAnalysis() {
        return array('Pmb',
                     'Suggestions',
                     'Review',
                     );
    }

    protected function getBasedPage($file) {
        static $baseHTML;

        if (empty($baseHTML)) {
            $baseHTML = file_get_contents("{$this->config->dir_root}/media/devfaceted/datas/base.html");
            $project_name = $this->config->project_name;

            $baseHTML = $this->injectBloc($baseHTML, 'EXAKAT_VERSION', Exakat::VERSION);
            $baseHTML = $this->injectBloc($baseHTML, 'EXAKAT_BUILD', Exakat::BUILD);
            $baseHTML = $this->injectBloc($baseHTML, 'PROJECT_NAME', $project_name);
            $baseHTML = $this->injectBloc($baseHTML, 'PROJECT_LETTER', strtoupper($project_name{0}));


            $menu = <<<MENU
        <!-- Sidebar Menu -->
        <ul class="sidebar-menu">
          <li class="header">&nbsp;</li>
          <!-- Optionally, you can add icons to the links -->

          <li class="active"><a href="index.html"><i class="fa fa-dashboard"></i> <span>Dashboard</span></a></li>

          <li><a href="pmb.html"><i class="fa fa-flag"></i> <span>Pmb reports</span></a></li>
          <li><a href="review.html"><i class="fa fa-flag"></i> <span>Review</span></a></li>
          <li><a href="suggestions.html"><i class="fa fa-flag"></i> <span>Suggestions</span></a></li>
          <li><a href="unusedparameters.html"><i class="fa fa-file-code-o"></i> <span>Unused parameters</span></a></li>

          <li><a href="files.html"><i class="fa fa-file-code-o"></i> <span>Files</span></a></li>
          <li><a href="analyzers.html"><i class="fa fa-line-chart"></i> <span>Analyzers</span></a></li>

          <li class="treeview">
            <a href="#"><i class="fa fa-sticky-note-o"></i> <span>Annexes</span><i class="fa fa-angle-left pull-right"></i></a>
            <ul class="treeview-menu">
              <li><a href="annex_settings.html"><i class="fa fa-circle-o"></i>Analyzer Settings</a></li>
              <li><a href="proc_analyzers.html"><i class="fa fa-circle-o"></i>Processed Analyzers</a></li>
              <li><a href="codes.html"><i class="fa fa-circle-o"></i>Codes</a></li>
              <li><a href="analyzers_doc.html"><i class="fa fa-circle-o"></i>Documentation</a></li>
              <li><a href="credits.html"><i class="fa fa-circle-o"></i>Credits</a></li>
            </ul>
          </li>
        </ul>
        <!-- /.sidebar-menu -->
MENU;

            $baseHTML = $this->injectBloc($baseHTML, 'SIDEBARMENU', $menu);
        }

        $subPageHTML = file_get_contents("{$this->config->dir_root}/media/devfaceted/datas/$file.html");
        $combinePageHTML = $this->injectBloc($baseHTML, 'BLOC-MAIN', $subPageHTML);

        return $combinePageHTML;
    }

    public function generate($folder, $name = self::FILE_FILENAME) {
        if ($name === self::STDOUT) {
            print "Can't produce PMB format to stdout\n";
            return false;
        }
        
        if ($missing = $this->checkMissingThemes()) {
            print "Can't produce PMB format. There are ".count($missing).' missing themes : '.implode(', ', $missing).".\n";
            return false;
        }

        $this->finalName = "$folder/$name";
        $this->tmpName   = "$folder/.$name";

        $this->projectPath = $folder;

        $this->initFolder();

        $this->generateUnusedParameter();
        print "generateUnusedParameter\n";

        $this->generateProcFiles();

        print "generateProcFiles\n";
        $this->generateDashboard();
        print "generateProcFiles\n";
//        $this->generateAnalyzers();
        print "generateProcFiles\n";
        $this->generateFiles();

        $this->generateSuggestions();
        print "Suggestions\n";
        $this->generateReview();
        print "generateReview\n";
        $this->generatePmb();
        print "generatePmb\n";
        

        // Annex
        $this->generateAnalyzerSettings();
        $analyzersList = array_merge($this->themes->getThemeAnalyzers($this->dependsOnAnalysis()));
        $analyzersList = array_unique($analyzersList);
        $this->generateDocumentation($analyzersList);
        $this->generateCodes();

        // Static files
        $files = array('credits');
        foreach($files as $file) {
            $baseHTML = $this->getBasedPage($file);
            $this->putBasedPage($file, $baseHTML);
        }

        $this->cleanFolder();
    }

    protected function generateReview() {
        $this->generateIssuesEngine('review',
                                    $this->getIssuesFaceted('Review') );
    }

    protected function generatePmb() {
        $this->generateIssuesEngine('pmb',
                                    $this->getIssuesFaceted('Pmb') );
    }

    private function generateUnusedParameter() {
        $results = new Results($this->sqlite, array('Pmb/Parametre'));
        $results->load();

        $issues = array_column($results->toArray(), 'fullcode');
        $counts = array_count_values($issues);

        $parametres = $this->loadIni('pmb_parameters.ini', 'parameters');
        $table = array();
        foreach($parametres as $p) {
            $table []= "<tr><td><span style=\"color: #0000BB\">$p</span></td><td>".($counts[$p] ?? 0)."</td></tr>\n";
        }

        $table = implode('', $table);

        $html = $this->getBasedPage('sortable_table');
        $html = $this->injectBloc($html, 'BLOC-JS', '<script src="scripts/datatables.js"></script>');
        $html = $this->injectBloc($html, 'TITLE', 'Paramètre PMB et usage');
        $html = $this->injectBloc($html, 'TABLE_HEADERS', '<td><span style=\"color: #0000BB\">Paramètre name</span></td><td>Compte</td>');
        $html = $this->injectBloc($html, 'TABLE_ROWS', $table);
        $this->putBasedPage('unusedParameters', $html);
    }

    protected function loadIni($file, $index = null) {
        $fullpath = "{$this->config->dir_root}/data/$file";

        if (file_exists($fullpath)) {
            $ini = parse_ini_file($fullpath, INI_PROCESS_SECTIONS);
        } elseif ((!is_null($this->config->ext)) && ($iniString = $this->config->ext->loadData("data/$file")) !== null) {
            $ini = parse_ini_string($iniString, INI_PROCESS_SECTIONS);
        } else {
            assert(false, "No INI for '$file'.");
        }
        
        static $cache;

        if (!isset($cache[$fullpath])) {
            foreach($ini as &$values) {
                if (isset($values[0]) && empty($values[0])) {
                    $values = '';
                }
            }
            unset($values);
            $cache[$fullpath] = $ini;
        }
        
        if ($index !== null && isset($cache[$fullpath][$index])) {
            return $cache[$fullpath][$index];
        }
        
        return $cache[$fullpath];
    }
}

?>