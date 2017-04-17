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
		echo color("Initing in: ".self::$home,BLUE)."\n";
		self::$modules = module_search(self::$home);
		if (is_array(self::$modules))
		{
			echo color("Found ".count(self::$modules)." modules:",BLUE)."\n";
			foreach (self::$modules as $module)
			{
				echo $module->name."\n";
			}
		}
		else
		{
			echo color("Found 0 modules",RED)."\n";
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
		echo color("Cleaning repo cache",BLUE)."\n";
		unlink (self::$cache);
	}
	public static function load ()
	{
		self::$repos = [];
		$repos_new = false;
		if (!file_exists(self::$cache))
		{
			echo color("Searching for repositories in ",BLUE).color(self::$repo,YELLOW)."\n";
			$result = scandir(self::$repo,1);
			if (is_array($result))
			{
				foreach ($result as $path)
				{
					if ($path!='.' && $path!='..' && !is_dir(self::$repo.'/'.$path))
					{
						$link = config (self::$repo.'/'.$path);
						echo color("Fetching repository ",BLUE).color($path,GREEN).color(" '".$link."'",LIME)."\n";
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
			echo color("Writing repository cache",BLUE)."\n";
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
			        		echo color("Invalid module config on line ".$line,RED);
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
			echo color("Error opening .src file", RED);
		}
	}
	public static function checkout ()
	{
		foreach (self::$modules as $module)
		{
			if ($module->chdir())
			{
				echo color("[".$module->name."]",GREEN)." ".color($module->path,CYAN).":".color($module->branch,YELLOW)." ";
				execute("git checkout ".$module->branch);
			}
		}
	}
	public static function status ()
	{
		foreach (self::$modules as $module)
		{
			if ($module->chdir())
			{
				echo color("[".$module->name."]",GREEN)." ".color($module->path,CYAN);

				$result = [];
				preg_match ('/^\*\s([a-zA-Z_0-9]+)/',execute("git branch"),$result);
				//debug (execute("git branch"),"dsadas");
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
	public static function sync ($argv)
	{
		if (!isset($argv[2]) || !$argv[2])
		{
			echo color ("Please specify commit title for sync", RED);
			exit;
		}
		$commit = $argv[2];
		if (!self::$modules)
		{
			echo color ("Module list empty", RED);
			exit;
		}
		foreach (self::$modules as $module)
		{
			if (!$module->exists())
			{
				echo color ("Path not exists ".$module->path(), RED);
				exit;
			}
		}
		foreach (self::$modules as $module)
		{
			if ($module->chdir())
			{
				echo "\n";
				echo color("[".$module->name."]",GREEN)." ".color($module->path,CYAN)." ";
				echo "\n------------------------\n";
				$result = execute("git status",true,GREEN);
				echo $result."\n";
				$result = strtolower($result);
				$changes = false;
				if (strpos($result,'nothing to commit')===false)
				{
					$changes = true;
					//echo color("Commiting",BLUE)."\n";
					echo execute ("git add --all", true,RED)."\n";
					echo execute ("git commit -a -m \"".$commit."\"",true,RED)."\n";
				}
				//echo color("Pulling",BLUE)."\n";
				echo execute ("git pull origin ".$module->branch, true)."\n";
				if ($changes)
				{
					//echo color("Pushing",BLUE)."\n";
					echo execute ("git push origin ".$module->branch, true, RED)."\n";
				}
			}
			else
			{
				echo color ("Could not change dir to ".$module->path(), MAROON)."\n";
			}
		}
	}
	public static function ls ()
	{
		if (!self::$repos)
		{
			echo color("Repository list empty!",RED)."\n";
			exit;
		}
		foreach (self::$repos as $name => $repo)
		{
			echo color($name,YELLOW)."\n";
		}
	}
	public static function add ($argv)
	{
		if (!isset($argv[2]) || !$argv[2])
		{
			echo color("Specify repository name!",RED)."\n";
			exit;
		}
		$repo = $argv[2];
		if (!isset(self::$repos[$repo]))
		{
			echo color("Unknown repository ".$repo,RED)."\n";
			exit;
		}
		if (!isset($argv[3]) || !$argv[3])
		{
			echo color("Specify path for repository",RED)."\n";
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
			echo color ("Folder already exists: ".self::$home.'/'.$path,RED)."\n";
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
			echo color ("Something failed?",RED);
		}
	}
	public static function help()
	{
		echo color("src add",YELLOW)." ".color("repo path [-sub]",RED)."\n    ".color("add source repository to project:",CYAN)."\n";
			echo "    ".color("src add jquery app/public/js/jquery",GREEN)."\n";
			echo "    ".color("src add core lib/core -sub",GREEN)." - add core as submodule\n";

		echo color("src status",YELLOW)."\n    ".color("list all modules and their src states",CYAN)."\n";

		echo color("src sync",YELLOW)." ".color("\"commit message\"",RED)."\n    ".color("sync all app modules\n\t1.commit\n\t2.pull\n\t3.push",CYAN)."\n";

		echo color("src init",YELLOW)."\n    ".color("init src",CYAN)."\n";

		echo color("src refresh",YELLOW)."\n    ".color("refresh repository cache",CYAN)."\n";

		echo color("src list",YELLOW)."\n    ".color("list all repositories",CYAN)."\n";

		echo color("src check[out]",YELLOW)."\n    ".color("switch to configured branches",CYAN)."\n";
	}
}