## JeJoo! - Just enough Joomla!
... is an intent to create a script capable of producing an actual **bare bone** version of the [Joomla! content management system](http://joomla.org)

This is a personal playground - not affiliated to or supported by the [Joomla! project](http://joomla.org) or [Open Source matters](http://osm.org)

The script uses a personal path to the Joomla! platform which can be set as ```$_SERVER['JOOMLA_PLATFORM_PATH']``` - please adjust it to your needs.

Directory layout:

```
<root>
  |_ checkouts - The actual clone of the CMS repository - will be created by the script
  |_ extract - The extracted packages - will be created by the script
  |_ stripped - The "stripped" (bare bone) CMS - will be created by the script
  
  |- elements.xml - The elements to process (package names with files and query specifications)
  |- jejo.php - The magic script to run
```

Just - have Fun ```=;)```
