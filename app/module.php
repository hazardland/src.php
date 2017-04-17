<?php

class module
{
	public $home;
	public $name;
	public $path;
	public $branch;
	public function __construct($home, $path, $name, $branch)
	{
		$this->home = $home;
		$this->path = $path;
		$this->name = $name;
		$this->branch = $branch;
	}
	public static function parse ($home, $line)
	{
    	$line = explode (' ', $line);
    	if (is_array($line))
    	{
    		$data = explode(':',$line[0]);
    		$path = $data[0];
    		if (isset($data[1]))
    		{
    			$branch = $data[1];
    		}
    		if (!isset($line[1]))
    		{
    			$name = null;
    		}
    		else
    		{
    			$name = $line[1];
    		}
    		if ($name===null && $path==='/')
    		{
    			$name = '/';
    		}
    		return new module($home, $path, $name, $branch);
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