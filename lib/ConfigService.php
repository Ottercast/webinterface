<?php 
class ConfigService
{
	public $config;
	public $device;

	public function load_config(string $config_file, string $default_config_file): array
	{
		// evil hack
		if (!file_exists($config_file))
		{
			$cmd = "mount -o ro " . escapeshellarg($this->find_config_partition()) . " /mnt";
			`$cmd`;
		}

		$config = parse_ini_file ( $config_file , true , INI_SCANNER_TYPED );
		if ($config === false)
		{
			$config = parse_ini_file ( $default_config_file , true , INI_SCANNER_TYPED );
		}

		$this->config = $config;
		return $config; 
	}

	public function save_config(string $config_file, array $config): bool
	{
		$config_string = $this->create_ini_string($config);

		if (file_get_contents($config_file) != $config_string)
		{
			// configuration has changed

			// even more evil hack
			$configpartition = escapeshellarg($this->find_config_partition());
			`mount -o rw $configpartition /mnt`;
			`mount -o remount,rw $configpartition`;

			file_put_contents($config_file, $config_string);

			`mount -o remount,ro /mnt`;
			return true; 
		}

		return false;
	}

	public function find_config_partition(): string
	{
		$matches = [];
		$re = '/(\/dev\/mmcblk[0-9]+p)([0-9+]) on \/ type /m';
		$mountpoints = `mount`; 
		preg_match_all($re, $mountpoints, $matches, PREG_SET_ORDER, 0);

		if (!isset($matches[0][2]))
		{
			error_log("Could not determine config partition! Stopping!");
			die();
		}

		$partitionID = ((int)$matches[0][2]) - 1;
		$device = $matches[0][1];

		return $device . (string)$partitionID;
	}

	public function create_ini_string ( array $config ): string
	{
		$result = "; OtterCast Configuration file\n".
				  "; Make sure to quote special characters, e.g. key = \"value\"\n\n";

		foreach ($config as $section => $elements)
		{
			$result .= "[" . $section . "]\n"; 
			foreach ($elements as $key => $value)
			{
				if (is_string($value))
				{
					$result .= $key . " = \"" . $value . "\"\n";
				}
				elseif (is_bool($value))
				{
					$result .= $key . " = " . ($value ? 'true' : 'false') . "\n";
				}
				else
				{
					$result .= $key . " = " . $value . "\n";
				}
			}
			$result .= "\n";
		}
		return $result;
	}

	public function put_file_if_different(string $filename, string $content): bool
	{
		if (trim(file_get_contents($filename)) != trim($content))
		{
			file_put_contents($filename, $content);
			return true;
		}

		return false; 
	}

	// singleton
	public static function current()
	{
		static $instance = null;
		return $instance = $instance ?? new self();
	}
}