#!/usr/bin/env php
<?php
/**
 * JEJoo - Just enough Joomla!
 *
 * A Joomla! CMS Distribution Builder
 *
 * Options:
 *
 * --help - Try this first =;)
 *
 * --cfgfile <filename> - Path to an elements.xml file (default is elements.xml)
 * --forcebuild - Force a build even if the repo has not changed.
 * --noremoveindexhtml - Do not remove superfluous index.html files.
 * --nocolors Don\'t be fancy ;)
 *
 * -q - Be quiet.
 *
 * @author Nikolai Plath - elkuku - 11/2011
 * @author - You ?
 *
 * @version 1.0
 *
 * @license GPL SA
 */

'cli' == PHP_SAPI || die('This script must be run from the command line');
version_compare(PHP_VERSION, '5.3.0') >= 0 || die('This script requires PHP 5.3');

//-- Watch out =;)
error_reporting(-1);

//-- We are a valid Joomla! entry point.
define('_JEXEC', 1);

//-- Bootstrap the Joomla! Platform.
require getenv('JOOMLA_PLATFORM_PATH').'/libraries/import.php';

define('JPATH_BASE', __DIR__);

define('JPATH_SITE', JPATH_BASE);//still married

jimport('joomla.application.cli');
jimport('joomla.filesystem.folder');
jimport('joomla.filesystem.file');

/**
 * JEJoo - Just enough Joomla!.
 *
 * A Joomla! CMS distribution builder.
 */
class JEJoo extends JApplicationCli
{
    private $xml = null;

    private function HELP()
    {
        $this->output('Usage:', true, 'green');

        $this->output('
jejoo.php [options]

--help - Try this first =;)

--cfgfile <filename> - Path to an elements.xml file (default is elements.xml).
--forcebuild - Force a build even if the repo has not changed.
--noremoveindexhtml - Do not remove superfluous index.html files.

--nocolors Don\'t be fancy ;)

-q - Be quiet.
');
        $this->output('=;)', true, 'green');
    }//function

    /**
     * Main function.
     *
     * @throws Exception
     *
     * @return bool
     */
    public function doExecute()
    {
        JLoader::registerPrefix('Ooo', JPATH_BASE.'/classes');
        
        $this->output('-------------------------------');
        $this->output('-- JeJoo - JustEnoughJoomla! --');
        $this->output('--  Joomla! Distro Builder   --');
        $this->output('-------------------------------');

        if($this->input->get('help'))
        {
            $this->HELP();

            return true;
        }

        $this->setup();

        if( ! $this->checkRepo())
        {
            $this->output();
            $this->output('Nothing has changed - come back later =;)', true, 'green');
            $this->output();

            return true;
        }

        $this->output('ok', true, 'green');

        $this
            ->processFiles()
            ->processQueries()
            ->processPatches();

        $this->output();
        $this->output('Finished =;)', true, 'green');
        $this->output();

        return true;
    }//function

    /**
     * Clean and create the build environment.
     *
     * @return JEJoo
     */
    private function setup()
    {
        if($this->input->get('nocolors'))
        {
            define('COLORS', 0);
        }
        else
        {
            //-- Got xampp and probs setting the include path ? eclipse ?..
            set_include_path(get_include_path().PATH_SEPARATOR.'/opt/lampp/lib/php');

            //-- El KuKu's ConsoleColor - see: http://elkuku.github.com/pear/
            @include 'elkuku/console/Color.php';

            //-- OR -- PEAR's ConsoleColor
            if( ! class_exists('Console_Color')) @include 'Console/Color.php';

            //-- Any color ?
            define('COLORS', class_exists('Console_Color'));
        }

        $cfg = JPATH_BASE.'/'.$this->input->get('cfgfile', 'elements.xml');

        if( ! file_exists($cfg))
        throw new Exception('The expected xml elements file has not been found in: '.$cfg);

        $this->xml = JFactory::getXML($cfg);

        if( ! $this->xml)
        throw new Exception('Invalid elements.xml file');

        define('PATH_BUILD', $this->config->get('buildPath'));
        define('PATH_TARGET', $this->config->get('targetPath'));

        $this->output('elements.xml: '.$cfg);
        $this->output('Build path  : '.PATH_BUILD);
        $this->output('Target path : '.PATH_TARGET);

        if( ! PATH_BUILD || ! is_dir(PATH_BUILD))
        throw new Exception('Please specify a valid build path in your configuration.php');

        if( ! PATH_TARGET || ! is_dir(PATH_TARGET))
        throw new Exception('Please specify a valid target path in your configuration.php');

        JFolder::delete(PATH_BUILD.'/packages');
        JFolder::delete(PATH_BUILD.'/barebone');

        JFolder::create(PATH_BUILD.'/packages/joomla');
        JFolder::create(PATH_BUILD.'/barebone');
        JFolder::create(PATH_BUILD.'/logs');

        return $this;
    }//function

    /**
     * Create or update from a GitHub repository
     *
     * @return boolean True if the repo has changed or has been created
     */
    private function checkRepo()
    {
        $this->output('Processing the base repo...', false, 'purple');

        $changed = false;

        if(false == file_exists(PATH_BUILD.'/checkout'))
        {
            //-- Clone repository
            $this->output('creating...', false, 'yellow');

            chdir(PATH_BUILD);
            passthru('git clone git://github.com/joomla/joomla-cms.git checkout 2>&1', $ret);
            
            chdir(PATH_BUILD.'/checkout');

            $changed = true;
        }
        else
        {
            //-- Update existing repository
            chdir(PATH_BUILD.'/checkout');

            $this->output('updating...', false, 'yellow');

            exec('git fetch origin');
            exec('git merge origin/master');
        }

        $actualSHA = exec('git rev-parse HEAD');

        if( ! JFile::exists(PATH_BUILD.'/logs/lastSHA'))
        {
            $this->output('No previous repo information found ! ...', false, 'yellow');
            $changed = true;
        }
        else
        {
            $lastSHA = JFile::read(PATH_BUILD.'/logs/lastSHA');

            if($lastSHA != $actualSHA)
            {
                $this->output('repo has changed ! ...', false, 'yellow');
                $changed = true;
            }
            else
            {
                $this->output('up to date...', false, 'green');
            }
        }

        $lastCommit = shell_exec('git log -n 1');

        JFile::write(PATH_BUILD.'/logs/lastSHA', $actualSHA);
        JFile::write(PATH_BUILD.'/logs/lastCommit', $lastCommit);

        $changed = $changed || $this->input->get('forcebuild');

        if($changed)
        {
            $this->output('copy to *barebone* folder...', false, 'yellow');

            JFolder::copy('checkout', 'barebone', PATH_BUILD, true);

            JFolder::delete(PATH_BUILD.'/barebone/.git');
        }

        return $changed;
    }//function

    /**
     * Copy and or remove files and folders.
     *
     * @return JEJoo
     */
    private function processFiles()
    {
        if( ! $this->input->get('noremoveindexhtml'))
        {
            $this->output('Removing superfluous index.html files...', true, 'purple', false);
            $indexhtmls = JFolder::files(PATH_BUILD.'/barebone', 'index.html', true, true);
            $this->output('found '.count($indexhtmls).'...', false);

            JFile::delete($indexhtmls);

            $this->output('ok');
        }

        $this->output('Processing removals...', true, 'purple');

        foreach($this->xml->removes->file as $file)
        {
            $this->output((string)$file.'...', false);

            if(JFile::delete(PATH_BUILD.'/barebone/'.$file))
            {
                $this->output('ok', true, 'green');
            }
            else
            {
                $this->output('FAILED', true, 'red');
            }
        }//foreach

        $this->output('Processing packages...', true, 'purple');

        /** @var $p SimpleXMLElement */
        foreach($this->xml->packages->package as $p)
        {
            $package = $p->attributes()->name;

            $this->output($package.'...', true, 'cyan');

            JFolder::create(PATH_BUILD.'/packages/joomla/'.$package);

            foreach($p->item as $item)
            {
                $this->output((string)$item.'...', false);

                JFolder::create(dirname(PATH_BUILD.'/packages/joomla/'.$package.'/'.$item));

                if(rename(PATH_BUILD.'/barebone/'.$item
                        , PATH_BUILD.'/packages/joomla/'.$package.'/'.$item))
                {
                    $this->output('ok', true, 'green');
                }
                else
                {
                    $this->output('FAILED', true, 'red');
                }
            }//foreach
        }//foreach

        return $this;
    }//function

    /**
     * Apply patches to the build.
     *
     * @return JEJoo
     */
    private function processPatches()
    {
        //-- Step 1: check if the patch(es) can be applied

        $files = JFolder::files(JPATH_BASE.'/patches', '.', false, true);

        chdir(PATH_BUILD.'/barebone');

        $errors = array();

        $this->output('Checking patches...', false);

        foreach($files as $file)
        {
            if(false !== strpos($file, 'BAD') || false !== strpos($file, 'xreadme.md'))
            continue;

            $test = shell_exec('git apply --check '.$file.' 2>&1');

            if($test)
            $errors[] = $test;
        }//foreach

        if($errors)
        {
            $this->output();
            $this->output('Unable to apply the following patch(es)', true, 'red');

            foreach($errors as $error)
            {
                $this->output($error, true, 'red');
            }//foreach

            //-- @todo throw an exception on error ¿

            return $this;
        }

        //-- Step 2: Apply the patch(es)

        $this->output('Applying patches...', false);

        foreach($files as $file)
        {
            if(false !== strpos($file, 'BAD'))
            continue;

            $name = JFile::getName($file);
            $test = shell_exec('git apply '.$file.' 2>&1');

            if($test)
            {
                $this->output($test, false, 'red');
            }
            else
            {
                $this->output(JFile::getName($file).'...', false);
            }
        }//foreach

        $this->output('ok', true, 'green');

        return $this;
    }//function

    /**
     * Method to process the standard Joomla! CMS install SQL file
     * according to specifiactions given in a xml file.
     *
     * Automagic =;)
     *
     * @throws Exception
     *
     * @return JEJoo
     */
    private function processQueries()
    {
        $this->output('Processing queries...', true, 'purple');

        if( ! file_exists(PATH_BUILD.'/barebone/installation/sql/mysql/joomla.sql'))
        throw new Exception('Joomla! SQL not found');

        $origSql = implode('', file(PATH_BUILD.'/barebone/installation/sql/mysql/joomla.sql'));

        $origQueries = $this->splitQueries($origSql);

        $queryResult = array();
        $stripResult = array();

        /** @var $p SimpleXMLElement */
        foreach($this->xml->packages->package as $p)
        {
            $package = (string)$p->attributes()->name;

            $this->output((string)$package, true, 'cyan');

            if( ! isset($stripResult[$package])) $stripResult[$package] = array();

            /** @var $query SimpleXMLElement */
            foreach($p->query as $query)
            {
                $command = (string)$query->attributes()->type;
                $table = (string)$query;
                $item = (string)$query->attributes()->item;

                $this->output($command.' - '.$table.'...', false);

                foreach($origQueries as $qNum => $query)
                {
                    $query = trim($query);
                    $pieces = explode("\n", $query);

                    $qCommand = $pieces[0];

                    $qParts = explode(' ', $qCommand);

                    if(strtolower($qParts[0]) != $command)
                    continue;

                    if( ! preg_match('/#__'.$table.'`/', $qCommand))
                    continue;

                    switch($command)
                    {
                        case 'create':
                            $stripResult[$package][] = $query;
                            $origQueries[$qNum] = '';
                            break;

                        case 'insert':
                            if( ! isset($item))
                            exit('No strip set: '.$line);

                            $strip = $item;

                            $qNew = array();
                            $qNew[] = $qCommand;

                            if(strpos($qCommand, 'VALUES') === false)
                            $qNew[] = 'VALUES';

                            $qOld = array();
                            $found = false;

                            foreach($pieces as $p)
                            {
                                if(preg_match('/'.$strip.'/', $p))
                                {
                                    $qNew[] = $p;
                                    $found = true;
                                }
                                else
                                {
                                    $qOld[] = $p;
                                }
                            }//foreach

                            $last = count($qNew) - 1;
                            $qNew[$last] = rtrim($qNew[$last], ',;');
                            $qNew[$last] .= ';';

                            $last = count($qOld) - 1;

                            if($last >= 0)
                            {
                                $qOld[$last] = rtrim($qOld[$last], ',;');
                                $qOld[$last] .= ';';
                            }

                            if($found)
                            {
                                $stripResult[$package][] = implode("\n", $qNew);
                                $origQueries[$qNum] = trim(implode("\n", $qOld));
                            }

                            break;

                        default:
                            //nodefault..
                            break;
                    }//switch
                }//foreach

                $this->output('ok', true, 'green');
            }//foreach
        }//foreach

        $this->output('Writing sql files...', true, 'purple');

        foreach($stripResult as $package => $queries)
        {
            $this->output($package.'...', false);

            if( ! $queries)
            {
                $this->output('no queries', true, 'yellow');

                continue;
            }

            $fName = PATH_BUILD.'/packages/joomla/'.$package.'/install.sql';
            $buffer = implode("\n\n", $queries);

            if( ! JFile::write($fName, $buffer))
            throw new Exception('Can not open '.$fName);

            $this->output('ok', true, 'green');
        }//foreach

        $this->output('ok', true, 'green');

        $this->output('Writing modified Joomla! sql file...', false, 'purple');

        $fName = PATH_BUILD.'/barebone/installation/sql/mysql/joomla.sql';
        $buffer = implode("\n\n", $origQueries);

        if( ! JFile::write($fName, $buffer))
        throw new Exception('Can not open '.$fName);

        $this->output('ok', true, 'green');

        return $this;
    }//function

    /**
     * Method to split up queries from a schema file into an array.
     *
     * @param string $sql SQL schema.
     *
     * @return array Queries to perform.
     */
    private function splitQueries($sql)
    {
        // Initialise variables.
        $buffer        = array();
        $queries    = array();
        $in_string    = false;

        // Trim any whitespace.
        $sql = trim($sql);

        // Remove comment lines.
        $sql = preg_replace("/\n\#[^\n]*/", '', "\n".$sql);

        // Parse the schema file to break up queries.
        for($i = 0; $i < strlen($sql) - 1; $i ++)
        {
            if($sql[$i] == ";" && ! $in_string)
            {
                $queries[] = trim(substr($sql, 0, $i + 1));
                $sql = substr($sql, $i + 1);
                $i = 0;
            }

            if($in_string
            && ($sql[$i] == $in_string)
            && $buffer[1] != "\\")
            {
                $in_string = false;
            }
            else if( ! $in_string
            && ($sql[$i] == '"' || $sql[$i] == "'")
            && ( ! isset ($buffer[0]) || $buffer[0] != "\\"))
            {
                $in_string = $sql[$i];
            }

            if(isset($buffer[1])) $buffer[0] = $buffer[1];

            $buffer[1] = $sql[$i];
        }//for

        // If the is anything left over, add it to the queries.
        if( ! empty($sql)) $queries[] = $sql;

        return $queries;
    }//function

    /**
     * Output a text string. Called with no parameters prints an empty line.
     *
     * @param string $text The text to display
     * @param bool $nl Should a new line be printed.
     * @param string $fg Foreground color.
     * @param string $bg Background color.
     *
     * @return void
     */
    private function output($text = '', $nl = true, $fg = '', $bg = '')
    {
        static $verbose = null;

        if(is_null($verbose)) $verbose =($this->input->get('q')) ? false : true;

        if( ! $verbose) return;

        if($fg && COLORS) $this->out(Console_Color::fgcolor($fg), false);
        if($bg && COLORS) $this->out(Console_Color::bgcolor($bg), false);

        $this->out($text, $nl);

        if(($fg || $bg) && COLORS) $this->out(Console_Color::convert('%n'), false);
    }//function
}//class

try
{
    // Execute the application.
    JApplicationCli::getInstance('JEJoo')->execute();

    exit(0);
}
catch(Exception $e)
{
    $m =(COLORS) ? Console_Color::convert('%7%r'.$e->getMessage().'%n') : $e->getMessage();

    fwrite(STDOUT, $m."\n");

    exit($e->getCode());
}//try
