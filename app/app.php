<?php

class app
{
	public static $cache;
	public static $home;
	public static $src;
	public static $repo;
	public static $modules = [];
	public static $repos = [];
	public static function init ()
	{
		echo \console\color("Initing in: ".self::$home,\console\BLUE)."\n";
		self::$modules = module_search(self::$home);
		if (is_array(self::$modules))
		{
			echo \console\color("Found ".count(self::$modules)." modules:",\console\BLUE)."\n";
			foreach (self::$modules as $module)
			{
				echo $module->name.':'.$module->branch."\n";
			}
		}
		else
		{
			echo \console\color("Found 0 modules",\console\RED)."\n";
		}
		//file_put_contents (self::$src,'');
		modules_save(self::$src);
		self::$modules = [];
	}
	public static function reinit ()
	{
		unlink (self::$src);
		self::init();
	}
	public static function refresh ()
	{
		echo \console\color("Cleaning repo cache",\console\BLUE)."\n";
		unlink (self::$cache);
	}
	public static function load ()
	{
		self::$repos = [];
		$repos_new = false;
		if (!file_exists(self::$cache))
		{
			echo \console\color("Searching for repositories in ",\console\BLUE).\console\color(self::$repo,\console\YELLOW)."\n";
			$result = scandir(self::$repo,1);
			if (is_array($result))
			{
				foreach ($result as $path)
				{
					if ($path!='.' && $path!='..' && !is_dir(self::$repo.'/'.$path))
					{
						$link = config (self::$repo.'/'.$path);
						echo \console\color("Fetching repository ",\console\BLUE).\console\color($path,\console\GREEN).\console\color(" '".$link."'",\console\LIME)."\n";
						if ($link)
						{
							$repos_new = true;
							repo_load ($link);
						}
					}
				}
			}
		}
		if (file_exists(self::$cache))
		{
			repo_load(self::$cache);
		}

		if ($repos_new)
		{
			echo \console\color("Writing repository cache",\console\BLUE)."\n";
			repo_cache(self::$cache);
		}

		/*
		 * collect module data
		 */
		self::$modules = [];
		if ($file = fopen(self::$src, 'r'))
		{
		    while(!feof($file))
		    {
		        $line = trim(fgets($file));
		        if ($line!='')
		        {
		        	if ($line[0]!='#')
		        	{
			        	$module = module::parse(self::$home, $line);
			        	if ($module->path==null)
			        	{
			        		echo \console\color("Invalid module config on line ".$line,\console\RED);
			        	}
			        	else
			        	{
			        		self::$modules[$module->path] = $module;
			        	}
		        	}
		        }
		    }
		    fclose($file);
		}
		else
		{
			echo \console\color("Error opening .src file", \console\RED);
		}
	}
	public static function checkout ()
	{
		foreach (self::$modules as $module)
		{
			if ($module->chdir())
			{
				echo \console\color("[".$module->name."]",\console\GREEN)." ".\console\color($module->path,\console\CYAN).":".\console\color($module->branch,\console\YELLOW)." ";
				execute("git checkout master");
			}
		}
	}
	public static function status ()
	{
		foreach (self::$modules as $module)
		{
			if ($module->chdir())
			{
				echo \console\color("[".$module->name."]",\console\GREEN)." ".\console\color($module->path,\console\CYAN);
				if (isset($result) && is_array($result) && isset($result[1]))
				{
					echo ":".\console\color(git_branch($module->path()),\console\YELLOW);
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
					echo \console\color("+".$modified,\console\RED);
				}
				echo "\n";
			}
			else
			{
				echo " ".\console\color("[bad path ".$module->path."]", \console\RED)."\n";
			}
		}
	}
	public static function sync ($argv)
	{
		if (!isset($argv[2]) || !$argv[2])
		{
			echo \console\color("Please specify commit title for sync", \console\RED);
			exit;
		}
		$commit = $argv[2];
		if (!self::$modules)
		{
			echo \console\color("Module list empty", \console\RED);
			exit;
		}
		foreach (self::$modules as $module)
		{
			if (!$module->exists())
			{
				echo \console\color("Path not exists ".$module->path(), \console\RED);
				exit;
			}
		}
		foreach (self::$modules as $module)
		{
			if ($module->chdir())
			{
				echo "\n";
				echo \console\color("[".$module->name."]",\console\GREEN)." ".\console\color($module->path,\console\CYAN)." ";
				echo "\n------------------------\n";
				$result = execute("git status",true,\console\GREEN);
				echo $result."\n";
				$result = strtolower($result);
				$changes = false;
				if (strpos($result,'nothing to commit')===false)
				{
					$changes = true;
					//echo \console\color("Commiting",\console\BLUE)."\n";
					echo execute ("git add --all", true,\console\RED)."\n";
					echo execute ("git commit -a -m \"".$commit."\"",true,\console\RED)."\n";
				}
				//echo \console\color("Pulling",\console\BLUE)."\n";
				echo execute ("git pull origin ".$module->branch, true)."\n";
				if ($changes)
				{
					//echo \console\color("Pushing",\console\BLUE)."\n";
					echo execute ("git push origin ".$module->branch, true, \console\RED)."\n";
				}
			}
			else
			{
				echo \console\color("Could not change dir to ".$module->path(), \console\MAROON)."\n";
			}
		}
	}
	public static function update ()
	{
		if (!self::$modules)
		{
			echo \console\color("Module list empty", \console\RED);
			exit;
		}
		foreach (self::$modules as $module)
		{
			if (!$module->exists())
			{
				echo \console\color("Path not exists ".$module->path(), \console\RED);
				exit;
			}
			if ($module->chdir())
			{
				$result = strtolower(execute("git status",true,\console\GREEN));
				if (strpos($result,'nothing to commit')===false)
				{
					echo \console\color("You have not commited changes in ".$module->path(), \console\MAROON)."\n";
					exit;
				}
			}
			else
			{
				echo \console\color("Could not change dir to ".$module->path(), \console\MAROON)."\n";
				exit;
			}
		}
		foreach (self::$modules as $module)
		{
			if ($module->chdir())
			{
				echo "\n";
				echo \console\color("[".$module->name."]",\console\GREEN)." ".\console\color($module->path,\console\CYAN)." ";
				echo "\n------------------------\n";
				$result = execute("git status",true,\console\GREEN);
				$result = strtolower($result);
				$changes = false;
				if (strpos($result,'nothing to commit')===false)
				{
					$changes = true;
				}
				echo execute ("git pull origin ".$module->branch, true)."\n";
			}
			else
			{
				echo \console\color("Could not change dir to ".$module->path(), \console\MAROON)."\n";
			}
		}
	}
	public static function ls ()
	{
		if (!self::$repos)
		{
			echo \console\color("Repository list empty!",\console\RED)."\n";
			exit;
		}
		foreach (self::$repos as $name => $repo)
		{
			echo \console\color($name,\console\YELLOW)."\n";
		}
	}
	public static function add ($argv)
	{
		if (!isset($argv[2]) || !$argv[2])
		{
			echo \console\color("Specify repository name!",\console\RED)."\n";
			exit;
		}
		$repo = $argv[2];
		if (!isset(self::$repos[$repo]))
		{
			echo \console\color("Unknown repository ".$repo,\console\RED)."\n";
			exit;
		}
		if (!isset($argv[3]) || !$argv[3])
		{
			echo \console\color("Specify path for repository",\console\RED)."\n";
			exit;
		}
		$path = $argv[3];
		if (isset($argv[4]) && ($argv[4]=='-sub' || $argv[4]=='--sub'))
		{
			$sub = true;
		}
		else
		{
			$sub = false;
		}
		if (is_dir(self::$home.'/'.$path))
		{
			echo \console\color("Folder already exists: ".self::$home.'/'.$path,\console\RED)."\n";
			exit;
		}
		//debug ($repos[$repo]);
		$link = self::$repos[$repo]['link'];
		$branch = self::$repos[$repo]['branch'];
		chdir(self::$home);
		if ($sub)
		{
			execute("git submodule add -b ".$branch." ".$link." ".$path, true);
		}
		else
		{
			execute("git clone ".$link." ".$path, true);
		}
		if (is_dir(self::$home.'/'.$path))
		{
			chdir(self::$home.'/'.$path);
			execute("git checkout ".$branch, true);
			self::$modules[$path] = new module (self::$home, $path, $repo, $branch);
			modules_save(self::$src);
		}
		else
		{
			echo \console\color("Something failed?",\console\RED);
		}
	}
	public static function help()
	{
		echo \console\color("src add",\console\YELLOW)." ".\console\color("repo path [-sub]",\console\RED)."\n    ".\console\color("add source repository to project:",\console\CYAN)."\n";
			echo "    ".\console\color("src add jquery app/public/js/jquery",\console\GREEN)."\n";
			echo "    ".\console\color("src add core lib/core -sub",\console\GREEN)." - add core as submodule\n";

		echo \console\color("src update",\console\YELLOW)."\n    ".\console\color("pull changes in all modules if you have nothing to commit",\console\CYAN)."\n";

		echo \console\color("src status",\console\YELLOW)."\n    ".\console\color("list all modules and their src states",\console\CYAN)."\n";

		echo \console\color("src sync",\console\YELLOW)." ".\console\color("\"commit message\"",\console\RED)."\n    ".\console\color("sync all app modules\n\t1.commit\n\t2.pull\n\t3.push",\console\CYAN)."\n";

		echo \console\color("src init",\console\YELLOW)."\n    ".\console\color("scan for git repos inside dir and init src",\console\CYAN)."\n";

		echo \console\color("src reinit",\console\YELLOW)."\n    ".\console\color("rescan dir and reinit src",\console\CYAN)."\n";

		echo \console\color("src refresh",\console\YELLOW)."\n    ".\console\color("refresh repository cache",\console\CYAN)."\n";

		echo \console\color("src list",\console\YELLOW)."\n    ".\console\color("list all repositories",\console\CYAN)."\n";

		echo \console\color("src check[out]",\console\YELLOW)."\n    ".\console\color("switch to configu\console\RED branches",\console\CYAN)."\n";
	}
}
