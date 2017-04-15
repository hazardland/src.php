<?php

	namespace Module;

	class Module
	{
		public $home;
		public $name;
		public $path;
		public function __construct($home, $line)
		{
			$this->home = $home;
	    	$line = explode (' ', $line);
	    	if (is_array($line))
	    	{
	    		$this->path = $line[0];
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
	    			$this->name = '*';
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
	}