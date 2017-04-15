<?php

	require __DIR__.'/lib/console/console.php';
	require __DIR__.'/lib/debug/debug.php';
	require __DIR__.'/lib/module/src/Module.php';

	function execute ($command)
	{
		$output = [];
		exec ($command, $output);
		return implode("\n", $output);
	}

	$home = getcwd();
	if (!file_exists($home.'/.app'))
	{
		echo color("Please cd to into folder containing file .app", RED);
		exit;
	}


	/*
	 * collect module data
	 */
	$modules = [];
	if ($file = fopen($home.'/.app', 'r'))
	{
	    while(!feof($file))
	    {
	        $line = trim(fgets($file));
	        if ($line!='')
	        {
	        	if ($line[0]!='#')
	        	{
		        	$module = new \Module\Module($home, $line);
		        	if ($module->path==null)
		        	{
		        		echo color("Invalid module config on line ".$line,RED);
		        	}
		        	else
		        	{
		        		$modules[$module->path] = $module;
		        	}
	        	}
	        }
	    }
	    fclose($file);
	}
	else
	{
		echo color("Error opening .app file", RED);
	}

	//debug ($modules);

	/**
	 * process command
	 */
	if (isset($argv[1]))
	{
		$command = strtolower($argv[1]);
		if ($command=='init')
		{
			foreach ($modules as $module)
			{
				if ($module->chdir())
				{
					echo color("[".$module->name."]",GREEN)." ".color($module->path,CYAN).":".color($module->branch,YELLOW)." ";
					execute("git checkout ".$module->branch);
				}
			}
		}
		else if ($command==='list')
		{
			foreach ($modules as $module)
			{
				if ($module->chdir())
				{
					echo color("[".$module->name."]",GREEN)." ".color($module->path,CYAN)." ";

					$result = [];
					preg_match ('/^\*\s([a-z]+)$/',execute("git branch"),$result);
					$branch = null;
					if (is_array($result) && isset($result[1]))
					{
						echo ":".color($result[1],YELLOW);
					}
					echo " ";
					$result = execute("git status");
					$changes = false;
					if (strpos($result,'nothing to commit')===false)
					{
						$modified = substr_count($result, 'modified:');
						if (!$modified)
						{
							$modified = '';
						}
						echo " ".color("+".$modified,RED);
					}
					echo "\n";
				}
				else
				{
					echo " ".color("[bad path ".$module->path."]", RED)."\n";
				}
			}
		}
		else if ($command==='sync')
		{
			if (!isset($argv[2]) || !$argv[2])
			{
				echo color ("Please specify commit title for sync", RED);
				exit;
			}
			$commit = $argv[2];
			if (!$modules)
			{
				echo color ("Module list empty", RED);
				exit;
			}
			foreach ($modules as $module)
			{
				if (!$module->exists())
				{
					echo color ("Path not exists ".$module->path(), RED);
					exit;
				}
			}
			foreach ($modules as $module)
			{
				if ($module->chdir())
				{
					echo "\n";
					echo color("[".$module->name."]",GREEN)." ".color($module->path,CYAN)." ";
					echo "\n------------------------\n";
					$result = strtolower(execute("git status"));
					$changes = false;
					if (strpos($result,'nothing to commit')===false)
					{
						$changes = true;
						echo color("Commiting",BLUE)."\n";
						echo execute ("git add --all")."\n";
						echo execute ("git commit -a -m \"".$commit."\"")."\n";
					}
					echo color("Pulling",BLUE)."\n";
					echo execute ("git pull origin ".$module->branch)."\n";
					if ($changes)
					{
						echo color("Pushing",BLUE)."\n";
						echo execute ("git push origin ".$module->branch)."\n";
					}
				}
				else
				{
					echo color ("Could not change dir to ".$module->path(), MAROON)."\n";
				}
			}
		}
		else
		{
			echo color("app list",YELLOW)." ".color("sync all app modules with states (1.commit,2.pull,3.push)",CYAN)."\n";
			echo color("app sync",YELLOW)." ".color("\"commit message\"",RED)." ".color("sync all app modules",CYAN)."\n";
		}
	}
