## JEJoo! - Just Enough Joomla!
... is an intent to create a script, capable of producing an always up to date **bare bone** version of the [Joomla! content management system](http://joomla.org)

### How does it work ?
The script does:

1. Clone the [current CMS repository](https://github.com/joomla/joomla-cms).
2. Strip out and save the "removable" packages defined in the elements.xml file.

The result is a "bare bone" Joomla! which is still installable and a fully functional CMS.<br />
The "additional" packages have been saved to separate folders and can be added later to create custom builds (blends ? ).<br />
Other (3rd party) packages and languages can be added.

### Usage
The script uses a personal path to the Joomla! platform which must be set as ```JOOMLA_PLATFORM_PATH``` - Please adjust this to your needs.

Open a command prompt, ```cd``` to the directory and run the script ```./jejo.php [options]```

The script produces colors if you have the PEAR [Console_Colors](http://pear.php.net/package/Console_Color) script installed.

### Additional parameters
* ```--help``` - Try this first =;)
* ```--cfgfile <filename>``` - Path to an elements.xml file (default is elements.xml)
* ```--forcebuild``` - Force a build even if the repo has not changed.
* ```--noremoveindexhtml``` - Do not remove superfluous index.html files.
* ```--nocolors``` Don't be fancy ;)
* ```-q``` - Be quiet.

### Directory layout:

```
<root>
  |_ checkout - The actual clone of the CMS repository - will be created by the script
  |_ extract - The extracted packages - will be created by the script
  |_ stripped - The "stripped" (bare bone) CMS - will be created by the script

  |- elements.xml - The elements to process (package names with files and query specifications)
  |- jejoo.php - The magic script to run
  |- readme.md - You are looking at it right now..
```

### History
This PHP script has been adapted from a bash script which has been created as a personal exercise in bash programming.

* The original project can be found here (still): [joomlacode.org/elkuku/jejo](http://joomlacode.org/gf/project/elkuku/scmsvn/?action=browse&path=%2Fjejo%2Ftrunk%2F)
* A web site to play around with it is here (still): http://jejo.der-beta-server.de/jejo - it should be fully functional, updated every 6 hours and able to produce custom builds.

## P.S. ...
I almost forgot to mention... This script has been created and tested in a **Linux environment**. If you encounter any kind of trouble (I believe you **will**), feel free to open a ticket here.

If you also have the solution to fix it, or would like to contribute in any other way - GitHub pull request are welcome.

Have Fun ```=;)```

### Note
This is a **personal playground** - not affiliated to or supported by the [Joomla! project](http://joomla.org) or [Open Source matters](http://osm.org)
