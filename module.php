<?php

	require __DIR__.'/lib/console/console.php';
	require __DIR__.'/lib/debug/debug.php';

	$home = getcwd();
	if (!file_exists($home.'/modules.php'))
	{
		echo color("Please cd to into your /app folder", RED);
		exit;
	}

	$modules = include $home.'/modules.php';

	//debug ($argv);
	//debug ($modules)

	if (isset($argv[1]))
	{
		$command = strtolower($argv[1]);
		if ($command==='sync')
		{
			if (!isset($argv[2]) || !$argv[2])
			{
				echo color ("Please specify commit title for sync", RED);
				exit;
			}
			$commit = $argv[2];
			if (!isset($modules) || !is_array($modules))
			{
				echo color ("Module list not found please check modules.php", RED);
				exit;
			}
			foreach ($modules as $name => $path)
			{
				if (!file_exists($home.'/'.$path))
				{
					echo color ("Path not exists ".$home.'/'.$path, RED);
					exit;
				}
			}
			foreach ($modules as $name => $path)
			{
				if (chdir($home.'/'.$path))
				{
					echo color("\nSyncing",GREEN)." ".color($name,YELLOW)."\n";
					$result = strtolower(exec("git status"));
					$changes = false;
					if (strpos($result,'nothing to commit')===false)
					{
						$changes = true;
						echo color("Commiting",BLUE)."\n";
						echo exec ("git add --all")."\n";
						echo exec ("git commit -a -m \"".$commit."\"")."\n";
					}
					echo color("Pulling",BLUE)."\n";
					echo exec ("git pull origin master")."\n";
					if ($changes)
					{
						echo color("Pushing",BLUE)."\n";
						echo exec ("git push origin master")."\n";
					}
				}
				else
				{
					echo color ("Could not change dir to ".$home.'/'.$path, MAROON)."\n";
				}
			}
		}
	}
