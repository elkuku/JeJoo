#!/usr/bin/env php
<?php
/**
 * JEJoo - Just enough Joomla!
 *
 * A Joomla! CMS Distribution Builder
 *
 * Options:
 *
 * --packages  Comma separated list of packages to build e.g. joomla.banners,myrepo.mypackage
 * --list      List available packages
 *
 * --verbose   Talk
 *
 * @author  Nikolai Plath - elkuku - 02/2012
 * @author  - You ?
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
require getenv('JOOMLA_PLATFORM_PATH') . '/libraries/import.php';

define('JPATH_BASE', __DIR__);
define('JPATH_SITE', JPATH_BASE); //still married

jimport('joomla.filesystem.folder');
jimport('joomla.filesystem.file');

/**
 * JEJoo - Just enough Joomla!
 *
 * The build script.
 *
 * A Joomla! CMS distribution builder.
 */
class JEJooBuilder extends JApplicationCli
{
	private $verbose = false;

	/**
	 * Main function.
	 *
	 * @throws Exception
	 *
	 * @return void
	 */
	public function doExecute()
	{
		$this->verbose = ($this->input->get('v') || $this->input->get('verbose')) ? true : false;

		define('PATH_BUILD', $this->config->get('buildPath'));
		define('PATH_TARGET', $this->config->get('targetPath'));
		define('PATH_TEMP', PATH_TARGET . '/temp');
		define('PATH_PACKAGES', PATH_BUILD . '/packages');

		define('COLORS', 0);

		if (!PATH_BUILD || false == is_dir(PATH_BUILD))
			throw new Exception('Please specify a valid build path in your configuration.php');

		if ($this->input->get('list'))
		{
			$this->listPackages();

			return;
		}

		if (!PATH_TARGET || false == is_dir(PATH_TARGET))
			throw new Exception('Please specify a valid target path in your configuration.php');

		$this->output('Target path : ' . PATH_TARGET);

		JFolder::delete(PATH_TEMP);
		JFolder::create(PATH_TEMP);

		$packages = $this->input->get('packages', '', 'html');

		$packages = ($packages) ? explode(',', $packages) : array();

		if (!count($packages))
			$this->output('Building a bare bone distro !');

		JFolder::copy(PATH_BUILD . '/barebone', PATH_TEMP, '', true);

		$sql = JFile::read(PATH_BUILD . '/barebone/installation/sql/mysql/joomla.sql');

		foreach ($packages as $package)
		{
			$this->output('Building: ' . $package);

			$parts = explode('.', $package);

			if (2 != count($parts))
				throw new Exception('Package must have the format "repository.package"');

			$path = PATH_PACKAGES . '/' . $parts[0] . '/' . $parts[1];

			$this->output($path);

			if (!is_dir($path))
				throw new Exception('Package not found in path: ' . $path);

			$folders = JFolder::folders($path);

			$sql .= JFile::read($path . '/install.sql');

			foreach ($folders as $folder)
			{
				JFolder::copy($path . '/' . $folder, PATH_TEMP . '/' . $folder, '', true);
			}
		}

		JFile::write(PATH_TEMP . '/installation/sql/mysql/joomla.sql', $sql);

		$this->output('Finished =;)');
	}

	private function listPackages()
	{
		$this->verbose = true;

		foreach (JFolder::folders(PATH_PACKAGES) as $repo)
		{
			$this->output('==============');
			$this->output($repo);
			$this->output('==============');

			foreach (JFolder::folders(PATH_PACKAGES . '/' . $repo) as $package)
			{
				$this->output($package);
			}
		}
	}

	/**
	 * Output a text string. Called with no parameters prints an empty line.
	 *
	 * @param string $text The text to display
	 * @param bool   $nl   Should a new line be printed.
	 * @param string $fg   Foreground color.
	 * @param string $bg   Background color.
	 *
	 * @return void
	 */
	private function output($text = '', $nl = true, $fg = '', $bg = '')
	{
		if (!$this->verbose) return;

		if ($fg && COLORS) $this->out(Console_Color::fgcolor($fg), false);
		if ($bg && COLORS) $this->out(Console_Color::bgcolor($bg), false);

		$this->out($text, $nl);

		if (($fg || $bg) && COLORS) $this->out(Console_Color::convert('%n'), false);
	}
}

try
{
	// Execute the application.
	JApplicationCli::getInstance('JEJooBuilder')->execute();

	exit(0);
}
catch (Exception $e)
{
	//	$m =(COLORS) ? Console_Color::convert('%7%r'.$e->getMessage().'%n') : $e->getMessage();
	$m = (COLORS) ? Console_Color::convert('%7%r' . $e->getMessage() . '%n') : $e->getMessage();

	fwrite(STDOUT, $m . "\n");

	exit($e->getCode());
}//try
