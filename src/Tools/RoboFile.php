<?php
/**
 * This is project's console commands configuration for Robo task runner.
 *
 * @see http://robo.li/
 */

namespace Glpi\Tools;

class RoboFile extends \Robo\Tasks
{
   protected $csignore = ['/vendor/'];
   protected $csfiles  = ['./'];

   /**
    * Minify all
    *
    * @return void
    */
   public function minify() {
      $this->minifyCSS()
         ->minifyJS();
   }

   /**
    * Minify CSS stylesheets
    *
    * @return void
    */
   public function minifyCSS() {
      $css_dir = './css';
      if (is_dir($css_dir)) {
         foreach (glob("$css_dir/*.css") as $css_file) {
            if (!$this->endsWith($css_file, 'min.css')) {
               $this->taskMinify($css_file)
                  ->to(str_replace('.css', '.min.css', $css_file))
                  ->type('css')
                  ->run();
            }
         }
      }
      return $this;
   }

   /**
    * Minify JavaScript files stylesheets
    *
    * @return void
    */
   public function minifyJS() {
      $js_dir = './js';
      if (is_dir($js_dir)) {
         foreach (glob("$js_dir/*.js") as $js_file) {
            if (!$this->endsWith($js_file, 'min.js')) {
               $this->taskMinify($js_file)
                  ->to(str_replace('.js', '.min.js', $js_file))
                  ->type('js')
                  ->run();
            }
         }
      }
      return $this;
   }

   /**
    * Extract translatable strings
    *
    * @return $this
    */
   public function localesExtract() {
      $this->_exec('./vendor/bin/extract_template.sh');
      return $this;
   }

   /**
    * Push locales to transifex
    *
    * @param string $branch
    * @return $this
    */
   public function localesPush($branch = '') {
      $branch = (($branch) ? ' -b ' : '') . $branch;
      $this->_exec('tx push -s ' . $branch);
      return $this;
   }

   /**
    * Pull locales from transifex.
    *
    * @param integer $percent Completeness percentage, defaults to 70
    * @param string $branch
    * @return $this
    */
   public function localesPull($percent = 70, $branch = '') {
      $branch = (($branch) ? ' -b ' : '') . $branch;
      $this->_exec('tx pull -a --minimum-perc=' . $percent . $branch);
      return $this;
   }

   /**
    * Build MO files
    *
    * @return $this
    */
   public function localesMo() {
      $this->_exec('./vendor/bin/plugin-release --compile-mo');
      return $this;
   }

   /**
    * Extract and send locales
    *
    * @param string $branch
    * @return $this
    */
   public function localesSend($branch = '') {
      $this->localesExtract()
           ->localesPush($branch);
      return $this;
   }

   /**
    * Retrieve locales and generate mo files
    *
    * @param integer $percent Completeness percentage, defaults to 70
    * @param string $branch
    * @return $this
    */
   public function localesGenerate($percent = 70, $branch = '') {
      $this->localesPull($percent, $branch)
           ->localesMo();
      return $this;
   }

   /**
    * Code sniffer.
    *
    * Run the PHP Codesniffer on a file or directory.
    *
    * @param string $file    A file or directory to analyze.
    * @param array  $options Options:
    * @option $autofix Whether to run the automatic fixer or not.
    * @option $strict  Show warnings as well as errors.
    *    Default is to show only errors.
    *
    *    @return void
    */
   public function codeCs(
      $file = null,
      $options = [
         'autofix'   => false,
         'strict'    => false,
      ]
   ) {
      if ($file === null) {
         $file = implode(' ', $this->csfiles);
      }

      $csignore = '';
      if (count($this->csignore)) {
         $csignore .= '--ignore=';
         $csignore .= implode(',', $this->csignore);
      }

      $strict = $options['strict'] ? '' : '-n';

      $result = $this->taskExec("./vendor/bin/phpcs $csignore --standard=vendor/glpi-project/coding-standard/GlpiStandard/ {$strict} {$file}")->run();

      if (!$result->wasSuccessful()) {
         if (!$options['autofix'] && !$options['no-interaction']) {
            $options['autofix'] = $this->confirm('Would you like to run phpcbf to fix the reported errors?');
         }
         if ($options['autofix']) {
            $result = $this->taskExec("./vendor/bin/phpcbf $csignore --standard=vendor/glpi-project/coding-standard/GlpiStandard/ {$file}")->run();
         }
      }

      return $result;
   }


   /**
    * Checks if a string ends with another string
    *
    * @param string $haystack Full string
    * @param string $needle   Ends string
    *
    * @return boolean
    * @see http://stackoverflow.com/a/834355
    */
   private function endsWith($haystack, $needle) {
      $length = strlen($needle);
      if ($length == 0) {
         return true;
      }

      return (substr($haystack, -$length) === $needle);
   }
}
