<?php
$config_file = "/mnt/config.ini";
$default_config_file = __DIR__ . "/example_config.ini";
$ssh_key_file = "/mnt/ssh_authorized_keys";

$config = load_config($config_file, $default_config_file);

function load_config(string $config_file, string $default_config_file): array
{
	// evil hack
	if (!file_exists($config_file))
	{
		`mount -o ro /dev/mmcblk0p1 /mnt`;
	}

	$config = parse_ini_file ( $config_file , true , INI_SCANNER_TYPED );
	if ($config === false)
	{
		$config = parse_ini_file ( $default_config_file , true , INI_SCANNER_TYPED );
	}
	return $config; 
}

function save_config(string $config_file, array $config): bool
{
	$config_string = create_ini_string($config);

	if (file_get_contents($config_file) != $config_string)
	{
		// configuration has changed

		// even more evil hack
		`mount -o rw /dev/mmcblk0p1 /mnt`;
		`mount -o remount,rw /dev/mmcblk0p1`;

		file_put_contents($config_file, $config_string);

		`mount -o remount,ro /mnt`;
		return true; 
	}

	return false;
}

function create_ini_string ( array $config ): string
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