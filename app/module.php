<?php

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