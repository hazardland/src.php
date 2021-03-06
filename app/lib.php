<?php

function execute ($command, $debug=false, $color=\console\BLUE)
{
	$output = [];
	if ($debug)
	{
		echo \console\color($command, $color)."\n";
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
				app::$repos[$repo[0]]['link'] = $link;
				app::$repos[$repo[0]]['branch'] = $branch;
			}
		}
	}
}
function repo_cache ($path)
{
	if (is_array(app::$repos) && count(app::$repos))
	{
		$result = '';
		foreach (app::$repos as $name => $repo)
		{
			$result .= $name.'='.$repo['link'].' '.$repo['branch']."\n";
		}
		file_put_contents($path, $result);
	}
}
function modules_save ($path)
{
	if (is_array(app::$modules) && count(app::$modules))
	{
		$result = '';
		foreach (app::$modules as $name => $module)
		{
			$result .= $module->line()."\n";
		}
		file_put_contents($path, $result);
	}
}
function git_branch ($path)
{
	chdir($path);
	$result = [];
	preg_match ('/^\*\s([a-zA-Z_0-9]+)/',execute("git branch"),$result);
	if (is_array($result) && isset($result[1]))
	{
		return trim($result[1]);
	}
}

function module_search ($home,$path='',&$modules=[])
{
	$name = basename($home.$path);
	$result = scandir($home.$path,1);
	//debug ($name);
	//exit;
	if (is_array($result))
	{
		foreach ($result as $item)
		{
			if ($item!=='.' && $item!='..')
			{
				if ($item=='.git')
				{
					$modules[($path==''?'/':$path)] = new module($home, ($path==''?'/':$path), $name, git_branch($home.$path));
				}
				elseif (is_dir($home.$path.'/'.$item))
				{
					//debug ($home.$path.'/'.$item);
					module_search($home,$path.'/'.$item,$modules);
				}
			}
		}
	}
	return $modules;
}
