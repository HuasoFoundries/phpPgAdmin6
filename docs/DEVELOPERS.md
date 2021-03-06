## DEVELOPER INFO
--------------

phpPgAdmin6 is Free/Open Source software and contributions are welcome.

It is a hard fork of phpPgAdmin:
  https://github.com/phppgadmin/phppgadmin
  http://phppgadmin.sourceforge.net/doku.php

The main focus / need addressed by this fork was to move the project from an out-dated code-base to modern, standards compliant and namespaced code.

## SOURCE REPOSITORY
-----------------

phpPgAdmin6 uses git for source control management. The phpPgAdmin6 git repository
is hosted at github:

  https://github.com/HuasoFoundries/phpPgAdmin6

To clone the phpPgAdmin source to your development system, execute the following
command:

  git clone git://github.com/HuasoFoundries/phpPgAdmin6.git

After making changes, you can clone the phppgadmin repository on github and make a pull
request. For details on how to make pull requests, see:

  https://help.github.com/articles/using-pull-requests

Please note that submitting code is considered a transfer of copyright to the 
phpPgAdmin6 project. phpPgAdmin6 is made available under the GPL v2 license.

Push access to the main phpPgAdmin6 git repository can be granted to developers
with a track record of useful contributions to phpPgAdmin6 at the discretion
of the phpPgAdmin6 development team.
                            
## TIPS FOR DEVELOPERS
-------------------

When you submit code to phpPgAdmin6, format to PSR/2 Coding Style Guide.

http://www.php-fig.org/psr/psr-2/

Test your code properly! For example, if you are developing a feature to create
domains, try naming your domain all of the following:

	* "
	* '
	* \
	* words with spaces
	* <br><br><br>

Don't forget to make sure your changes still pass the existing Selenium test 
suite. Additionally, you should add or update the test suite as needed to 
cover your new features. 

If you are adding a new class function, be sure to use the "clean",
"fieldClean", "arrayClean" and "fieldArrayClean" functions to properly escape
odd characters in user input.  Examine existing functions that do similar
things to yours to get yours right.

When writing data to the display, you should always urlencode() variables in
HREFs and htmlspecialchars() variables in forms.  Rather than use action=""
attributes in HTML form elements use action="thisformname.php".  This
ensures that browsers remove query strings when expanding the given
relative URL into a full URL.

When working on database classes, always schema qualifing your SQL where it is 
possible with the current schema *$data->_schema* for pg73+ classes. Then don't
forget to write your method for older classes which doesn't suppport schemas.
When working with git, always make sure to do a 'git pull' both before you 
start; so you have the latest code to work with; and also again before you 
create your patch; to minimize the chance of having conflicts. If you plan to 
submit your code via github pull requests, we strongly recommend doing your 
work in a feature specific branch. If you want to submit multiple patches, 
they should all live in thier own branch. Remeber, smaller changes are easier
to review, approve, and merge.


## COMMON VARIABLES
----------------

$data - A data connection to the current or default database.
$misc - Contains miscellaneous functions.  eg. printing headers & footers, etc.
$lang - Global array containing translated strings.  The strings in this array 
        have already been converted to HTML, so you should not 
        htmlspecialchars() them.
$conf - Global array of configuration options.

## WORKING WITH RECORDSETS
-----------------------

phpPgAdmin uses the ADODB database library for all its database access.  We have
also written our own wrapper around the ADODB library to make it more object
oriented (ADODB_base.pclass).

This is the general form for looping over a recordset:

$rs = $class->getResults();
if (is_object($rs) && $rs->recordCount() > 0) {
	while (!$rs->EOF) {
		echo $rs->fields['field'];
		$rs->moveNext();
	}
}
else echo "No results.";

## UPDATING LANGUAGE FILES FOR THE MONO-LINGUAL
--------------------------------------------

If you need to add or modify language strings for a new feature, the preferred
method is:

* cd into lang/ subdirectory
* modify english.php file only! 

If you've done it correctly, when you create your patch, it should only have 
diffs of the lang/english.php file. For more information on how the language 
system works, please see the TRANSLATORS file.


## UNDERSTANDING THE WORK/BRANCH/TAG/RELEASE PROCESS
------------------------------------------------- 

All new work for phpPgAdmin6 is done against the git develop branch. When we release a new revision, we tag that at release time using semantic versioning.

## GETTING HELP
------------

Use github issues to discuss development, such as proposing new features.

https://github.com/HuasoFoundries/phpPgAdmin6/issues