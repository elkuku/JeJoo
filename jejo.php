#!/usr/bin/php
<?php
/**
 * JeJoo - Just enugh Joomla!
 *
 * @author Nikolai Plath - elkuku - 11/2011
 * @author - You ?
 *
 * @version 1.0
 *
 * @license GPL SA
 */

'cli' == PHP_SAPI || die('This script must be run from the command line');

//-- watch out =;)
error_reporting(-1);

//-- got xampp and probs setting the include path ?..
set_include_path(get_include_path().PATH_SEPARATOR.'/opt/lampp/lib/php');

//-- KuKu's ConsoleColor - see: http://elkuku.github.com/pear/
@require 'elkuku/console/Color.php';

//-- OR -- PEAR's ConsoleColor
if( ! class_exists('Console_Color')) @require 'Console/Color.php';

//-- Any color ?
define('COLORS', class_exists('Console_Color'));

//-- We are a valid Joomla! entry point.
define('_JEXEC', 1);

//-- Bootstrap the Joomla! Platform.
require_once $_SERVER['JOOMLA_PLATFORM_PATH'].'/libraries/import.php';

define('JPATH_BASE', dirname(__FILE__));
define('JPATH_SITE', JPATH_BASE);

jimport('joomla.application.cli');
jimport('joomla.filesystem.folder');
jimport('joomla.filesystem.file');

/**
 * JeJoo - Just enough Joomla!
 *
 * A Joomla! CMS distribution builder.
 */
class JeJoo extends JCli
{
	private $verbose = true;

	private $xml = null;

	/**
	 * Main function.
	 *
	 * @throws Exception
	 */
	public function execute()
	{
		$this->output('-------------------------------', true, 'green');
		$this->output('-- JeJoo - JustEnoughJoomla! --', true, 'green');
		$this->output('--  Joomla! Distro Builder   --', true, 'green');
		$this->output('-------------------------------', true, 'green');
		$this->output(Console_Color::convert('%n'));

		$cfg = JPATH_BASE.'/elements.xml';

		if( ! file_exists($cfg))
		throw new Exception('The expected xml elements file has not been found in: '.$cfg);

		$this->xml = JFactory::getXML($cfg);

		$this->clean();

		$this->output('Processing the base repo...', false, 'green');

		if( ! $this->checkRepo())
		{
			$this->output();
			$this->output('Nothing has changed - come back later =;)', true, 'green');
			$this->output();

			return;
		}

		$this->output('OK');

		$this->output('Processing removals...', true, 'green');

		foreach($this->xml->removes->file as $file)
		{
			$this->output((string)$file);
			JFile::delete(JPATH_BASE.'/stripped/'.$file);
		}

		$this->output('Processing packages...', true, 'green');

		foreach($this->xml->packages->package as $package)
		{
			$this->output((string)$package->name, true, 'yellow');

			JFolder::create(JPATH_BASE.'/extract/'.$package->name);

			foreach($package->item as $item)
			{
				$this->output((string)$item);
					
				if(is_dir(JPATH_BASE.'/stripped/'.$item))
				{
					JFolder::create(JPATH_BASE.'/extract/'.$package->name.'/'.$item);
				}
				else
				{
					JFolder::create(dirname(JPATH_BASE.'/extract/'.$package->name.'/'.$item));
				}

				rename(JPATH_BASE.'/stripped/'.$item, JPATH_BASE.'/extract/'.$package->name.'/'.$item);
			}//foreach

		}//foreach

		$this->output('Processing queries...', true, 'green');

		$this->processQueries();

		/*
		 # Creating archives
		echo "Creating archives..." >> $LOG

		COMPRESSED=$JEJO_WEB_PATH/builds/jejo

		mkdir -p $COMPRESSED >> $LOG

		cd $BASE/export
		tar -czf $COMPRESSED/Joomla_trunk_export_rev_$REVNO.tar.gz *

		cd $BASE/stripped_nolibs
		tar -czf $COMPRESSED/Joomla_JEJo_NOLIBS_rev_$REVNO.tar.gz *

		cd $BASE/fishbone
		tar -czf $COMPRESSED/Joomla_Fishbone_rev_$REVNO.tar.gz *

		cd $WEB/stripped
		tar -czf $COMPRESSED/Joomla_JEJo_rev_$REVNO.tar.gz *
		logTime
		*/

		$this->output();
		$this->output('Finished =;)', true, 'green');
		$this->output();
	}

	/**
	 * Clean and create the build environment.
	 *
	 * @return void
	 */
	private function clean()
	{
		JFolder::delete(JPATH_BASE.'/extract');
		JFolder::create(JPATH_BASE.'/extract');
		JFolder::delete(JPATH_BASE.'/stripped');
		JFolder::create(JPATH_BASE.'/stripped');
	}

	/**
	 * Create or update from a GitHub repository
	 *
	 * @return boolean True if the repo has changed or has been created
	 */
	private function checkRepo()
	{
		$changed = false;

		if(!file_exists(JPATH_BASE.'/checkout'))
		{
			//-- Clone repository
			$this->output('creating...', false);

			chdir(JPATH_BASE);
			exec('git clone git@github.com:joomla/joomla-cms.git checkout');
			chdir(JPATH_BASE.'/checkout');

			$changed = true;
		}
		else
		{
			//-- Update existing repository
			$this->output('updating...', false);

			chdir(JPATH_BASE.'/checkout');
			exec('git fetch origin');
			exec('git merge origin/master');
		}

		$actualSHA = exec('git rev-parse HEAD');

		if( ! JFile::exists(JPATH_BASE.'/lastSHA'))
		{
			JFile::write(JPATH_BASE.'/lastSHA', $actualSHA);
			$changed = true;
		}
		else
		{
			$lastSHA = JFile::read(JPATH_BASE.'/lastSHA');

			if($lastSHA != $actualSHA)
			{
				$this->output('repo has changed ! ...', false);
				$changed = true;
			}
			else
			{
				$this->output('up to date...', false);
			}
		}

		$changed = true;//@todo REMOVEME !!!

		if($changed)
		{
			$this->output('copy to *stripped* folder...', false);

			if(JFolder::exists(JPATH_BASE.'/stripped')) JFolder::delete(JPATH_BASE.'/stripped');

			JFolder::copy(JPATH_BASE.'/checkout', JPATH_BASE.'/stripped');

			JFolder::delete(JPATH_BASE.'/stripped/.git');
		}

		return $changed;
	}//function

	/**
	 * Method to process the standard Joomla! install sql file according to
	 * specifiactions given in a xml file.
	 *
	 * @throws Exception
	 *
	 * @return boolean
	 */
	private function processQueries()
	{
		if( ! file_exists(JPATH_BASE.'/stripped/installation/sql/mysql/joomla.sql'))
		throw new Exception('Joomla! SQL not found');

		$origSql = implode('', file(JPATH_BASE.'/stripped/installation/sql/mysql/joomla.sql'));

		$origQueries = $this->splitQueries($origSql);

		$queryResult = array();
		$stripResult = array();

		foreach($this->xml->packages->package as $p)
		{
			$package = (string)$p->name;
			$this->output((string)$package, true, 'yellow');

			if( ! isset($stripResult[$package])) $stripResult[$package] = array();

			foreach($p->query as $query)
			{
				$command = (string)$query->attributes()->type;
				$table = (string)$query;
				$item = (string)$query->attributes()->item;

				$this->output("...$command - $table...");

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
							break;
					}//switch
				}//foreach
			}//foreach
		}//foreach

		// 		DProfiler::markFinished();

		$this->output('Writing sql files...', true, 'green');

		foreach($stripResult as $package => $queries)
		{
			$this->output('...'.$package.'...');

			$fName = JPATH_BASE.'/extract/'.$package.'/install.sql';
			$handle = fopen($fName, 'w');

			if( ! $handle)
			throw new Exception('Can not open '.$fName);

			fwrite($handle, implode("\n\n", $queries));
		}//foreach

		// 		DProfiler::markFinished();

		$this->output('Writing modified Joomla! sql file...', true, 'green');

		$handle = fopen(JPATH_BASE.'/stripped/installation/sql/mysql/joomla.sql', 'w');

		fwrite($handle, implode("\n\n", $origQueries));

		// 		DProfiler::markFinished();

		//-- Dis is a test..
		//-- Absolute paths should be cleaned by the following bash script
		// 		echo $TEST_UndefinedVar;

		return true;
	}//function

	/**
	 * Method to split up queries from a schema file into an array.
	 *
	 * @access	protected
	 * @param	string	SQL schema.
	 * @return	array	Queries to perform.
	 * @since	1.0
	 */
	private function splitQueries($sql)
	{
		// Initialise variables.
		$buffer		= array();
		$queries	= array();
		$in_string	= false;

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

	private function output($text = '', $nl = true, $fg = '', $bg = '')
	{
		if( ! $this->verbose) return;

		if($fg && COLORS) $this->out(Console_Color::fgcolor($fg), false);
		if($bg && COLORS) $this->out(Console_Color::bgcolor($bg), false);

		$this->out($text, $nl);

		if(($fg || $bg) && COLORS) $this->out(Console_Color::convert('%n'), false);
	}//function

}//class

try
{
	// Execute the application.
	JCli::getInstance('JeJoo')->execute();

	exit(0);
}
catch (Exception $e)
{
	// An exception has been caught, just echo the message.
	$m =(COLORS) ? Console_Color::convert('%7%r'.$e->getMessage().'%n') : $e->getMessage();

	fwrite(STDOUT, $m . "\n");

	exit($e->getCode());
}//try
