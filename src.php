<?php

	require __DIR__.'/lib/console/console.php';
	require __DIR__.'/lib/debug/debug.php';

	class module
	{
		public $home;
		public $name;
		public $path;
		public $branch;
		public function __construct($home, $line)
		{
			$this->home = $home;
	    	$line = explode (' ', $line);
	    	if (is_array($line))
	    	{
	    		$data = explode(':',$line[0]);
	    		$this->path = $data[0];
	    		if (isset($data[1]))
	    		{
	    			$this->branch = $data[1];
	    		}
	    		if (!isset($line[1]))
	    		{
	    			$this->name = null;
	    		}
	    		else
	    		{
	    			$this->name = $line[1];
	    		}
	    		if ($this->name===null && $this->path==='/')
	    		{
	    			$this->name = '/';
	    		}
	    	}
		}
		public function path()
		{
			return $this->home.'/'.$this->path;
		}
		public function exists()
		{
			return file_exists($this->path());
		}
		public function chdir()
		{
			if ($this->exists())
			{
				return chdir ($this->path());
			}
			return false;
		}
		public function line()
		{
			return $this->path.':'.$this->branch.' '.$this->name;
		}
	}
	function execute ($command, $debug=false, $color=BLUE)
	{
		$output = [];
		if ($debug)
		{
			echo color ($command, $color)."\n";
		}
		exec ($command, $output);
		return implode("\n", $output);
	}

	function config ($file)
	{
		$content = trim(file_get_contents($file));
		//debug ($content,'content');
		$content = preg_replace(['/[\s]+\n/', '/\n{1,}+/', '/\#.+\n/'], ["\n","\n",''], $content);
		return $content;
	}

	function repo_load ($link)
	{
		$content = config ($link);
		if ($content)
		{
			$items = explode("\n",$content);
			foreach ($items as $item)
			{
				$repo = explode('=',$item);
				if (count($repo)>1)
				{
					$data = explode(' ',$repo[1]);
					$link = $data[0];
					if (count($data)>1)
					{
						$branch = $data[1];
					}
					else
					{
						$branch = 'master';
					}
					$GLOBALS['repos'][$repo[0]]['link'] = $link;
					$GLOBALS['repos'][$repo[0]]['branch'] = $branch;
				}
			}
		}
	}
	function repo_cache ($path)
	{
		if (is_array($GLOBALS['repos']) && count($GLOBALS['repos']))
		{
			$result = '';
			foreach ($GLOBALS['repos'] as $name => $repo)
			{
				$result .= $name.'='.$repo['link'].' '.$repo['branch']."\n";
			}
			file_put_contents($path, $result);
		}
	}
	function modules_save ($path)
	{
		if (is_array($GLOBALS['modules']) && count($GLOBALS['modules']))
		{
			$result = '';
			foreach ($GLOBALS['modules'] as $name => $module)
			{
				$result .= $module->line()."\n";
			}
			file_put_contents($path, $result);
		}
	}

	$home = getcwd();

	if (isset($argv[1]) && $argv[1]=='init' && !file_exists($home.'/.src'))
	{
		echo color("Initing in: ".$home,BLUE)."\n";
		if (!file_exists($home.'/.git'))
		{
			execute ("git init",true);
		}
		file_put_contents ($home.'/.src','');
	}

	if (!file_exists($home.'/.src'))
	{
		echo color("Please cd to into folder containing file .src\n", RED);
		exit;
	}

	if (isset($argv[1]) && $argv[1]=='refresh' && file_exists(__DIR__.'/.cache'))
	{
		echo color("Cleaning repo cache",BLUE)."\n";
		unlink (__DIR__.'/.cache');
	}
	$repos = [];
	$repos_new = false;
	if (!file_exists(__DIR__.'/.cache'))
	{
		echo color("Searching for repositories in ",BLUE).color(__DIR__.'/repos',YELLOW)."\n";
		$result = scandir(__DIR__.'/repos',1);
		if (is_array($result))
		{
			$repo = __DIR__.'/repos/';
			foreach ($result as $path)
			{
				if ($path!='.' && $path!='..' && !is_dir($repo.$path))
				{
					$link = config ($repo.$path);
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
	if (file_exists(__DIR__.'/.cache'))
	{
		repo_load(__DIR__.'/.cache');
	}

	if ($repos_new)
	{
		echo color("Writing repository cache",BLUE)."\n";
		repo_cache(__DIR__.'/.cache');
	}

	/*
	 * collect module data
	 */
	$modules = [];
	if ($file = fopen($home.'/.src', 'r'))
	{
	    while(!feof($file))
	    {
	        $line = trim(fgets($file));
	        if ($line!='')
	        {
	        	if ($line[0]!='#')
	        	{
		        	$module = new module($home, $line);
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
		echo color("Error opening .src file", RED);
	}

	//debug ($modules);

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
		foreach ($modules as $module)
		{
			if ($module->chdir())
			{
				echo color("[".$module->name."]",GREEN)." ".color($module->path,CYAN).":".color($module->branch,YELLOW)." ";
				execute("git checkout ".$module->branch);
			}
		}
	}
	else if ($command==='status')
	{
		foreach ($modules as $module)
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
	else if ($command==='refresh')
	{
		//command is already processe above
	}
	else if ($command==='init')
	{
		//command is already processe above
	}
	else if ($command=='list')
	{
		if (!$repos)
		{
			echo color("Repository list empty!",RED)."\n";
			exit;
		}
		foreach ($repos as $name => $repo)
		{
			echo color($name,YELLOW)."\n";
		}
	}
	else if ($command==='add')
	{
		if (!isset($argv[2]) || !$argv[2])
		{
			echo color("Specify repository name!",RED)."\n";
			exit;
		}
		$repo = $argv[2];
		if (!isset($repos[$repo]))
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
		if (is_dir($home.'/'.$path))
		{
			echo color ("Folder already exists: ".$home.'/'.$path,RED)."\n";
			exit;
		}
		//debug ($repos[$repo]);
		$link = $repos[$repo]['link'];
		$branch = $repos[$repo]['branch'];
		chdir($home);
		if ($sub)
		{
			execute("git submodule add -b ".$branch." ".$link." ".$path, true);
		}
		else
		{
			execute("git clone ".$link." ".$path, true);
		}
		if (is_dir($home.'/'.$path))
		{
			chdir($home.'/'.$path);
			execute("git checkout ".$branch, true);
			$modules[$path] = new module ($home, $path.':'.$branch.' '.$repo);
			modules_save($home.'/.src');
		}
		else
		{
			echo color ("Something failed?",RED);
		}
	}
	else
	{
		echo color("src add",YELLOW)." ".color("repo path [-sub]",RED)."\n    ".color("add source repository to project:",CYAN)."\n";
			echo "    ".color("src add jquery app/public/js/jquery",GREEN)."\n";
			echo "    ".color("src add core lib/core -sub",GREEN)." - add core as submodule\n\n";

		echo color("src status",YELLOW)."\n    ".color("list all modules and their src states",CYAN)."\n\n";

		echo color("src sync",YELLOW)." ".color("\"commit message\"",RED)."\n    ".color("sync all app modules\n\t1.commit\n\t2.pull\n\t3.push",CYAN)."\n\n";

		echo color("src init",YELLOW)."\n    ".color("init src with git",CYAN)."\n\n";

		echo color("src refresh",YELLOW)."\n    ".color("refresh repository cache",CYAN)."\n";

		echo color("src list",YELLOW)."\n    ".color("list all repositories",CYAN)."\n";

		echo color("src check[out]",YELLOW)."\n    ".color("switch to configured branches",CYAN)."\n";
	}
