<?php

	require __DIR__.'/lib/console/console.php';
	require __DIR__.'/lib/debug/debug.php';

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
	        	$line = explode (' ', $line);
	        	if (is_array($line))
	        	{
	        		$path = $line[0];
	        		if (!isset($line[1]))
	        		{
	        			$name = null;
	        		}
	        		else
	        		{
	        			$name = $line[1];
	        		}
	        		$modules [$path] = $name;
	        	}
	        }
	    }
	    fclose($file);
	}
	else
	{
		echo color("Error opening .app file", RED);
	}


	/**
	 * process command
	 */
	if (isset($argv[1]))
	{
		$command = strtolower($argv[1]);
		if ($command=='list')
		{
			foreach ($modules as $path => $name)
			{
				if ($path==='/' && $name===null)
				{
					$name = '*';
				}
				if ($name!==null)
				{
					echo color ($name,YELLOW)." ";
				}
				if ($name!=$path)
				{
					echo color($path,CYAN);
				}
				if (file_exists($home.'/'.$path) && chdir($home.'/'.$path))
				{
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
					echo " ".color("[bad path ".$path."]", RED)."\n";
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
			foreach ($modules as $path => $name)
			{
				if (!file_exists($home.'/'.$path))
				{
					echo color ("Path not exists ".$home.'/'.$path, RED);
					exit;
				}
			}
			foreach ($modules as $path => $name)
			{
				if (chdir($home.'/'.$path))
				{
					if ($path==='/' && $name===null)
					{
						$name = '*';
					}
					if ($name===null)
					{
						$name = $path;
					}
					echo color("\nSyncing",GREEN)." ".color($name,PINK)."\n";
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
					echo execute ("git pull origin master")."\n";
					if ($changes)
					{
						echo color("Pushing",BLUE)."\n";
						echo execute ("git push origin master")."\n";
					}
				}
				else
				{
					echo color ("Could not change dir to ".$home.'/'.$path, MAROON)."\n";
				}
			}
		}
	}
