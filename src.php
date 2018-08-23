<?php

	require __DIR__.'/lib/console/console.php';
	require __DIR__.'/lib/debug/debug.php';
	require __DIR__.'/app/app.php';
	require __DIR__.'/app/lib.php';
	require __DIR__.'/app/module.php';

	app::$home = getcwd();
	app::$cache = __DIR__.'/.cache';
	app::$src = app::$home.'/.src';
	app::$repo = __DIR__.'/repos';

	if (isset($argv[1]) && $argv[1]=='reinit')
	{
		app::reinit();
	}

	if (isset($argv[1]) && $argv[1]=='init' && !file_exists(app::$src))
	{
		app::init ();
	}

	if (!file_exists(app::$src))
	{
		echo \console\color("Please cd to into folder containing file .src\n", \console\RED);
		exit;
	}

	if (isset($argv[1]) && $argv[1]=='refresh' && file_exists(app::$cache))
	{
		app::refresh();
	}

	app::load();

	/**
	 * process command
	 */
	$command = '';
	if (isset($argv[1]))
	{
		$command = strtolower($argv[1]);
	}

	if ($command=='checkout' || $command=='check')
	{
		app::checkout();
	}
	else if ($command==='status')
	{
		app::status();
	}
	else if ($command==='sync')
	{
		app::sync($argv);
	}
	else if ($command==='refresh')
	{
		//command is already processe above
	}
	else if ($command==='init')
	{
		//command is already processe above
	}
	else if ($command==='reinit')
	{
		//command is already processe above
	}
	else if ($command=='list')
	{
		app::ls();
	}
	else if ($command==='add')
	{
		app::add ($argv);
	}
	else if ($command==='update')
	{
		app::update ();
	}
	else
	{
		app::help();
	}
