<?php

$config_file = "/mnt/config.sh";

// evil hack
if (!file_exists($config_file))
{
	`mount -o rw /dev/mmcblk0p1 /mnt`;
}

// Config parser
$cmd = 'bash -c "source ' . escapeshellarg($config_file) . ' && declare"';
$configraw = explode("\n", `$cmd`);
$config = []; 
foreach ($configraw as $configline)
{
	if (strpos($configline, "CONFIG_") === 0)
	{
		$configitem = explode("=", $configline, 2);
		$config[$configitem[0]] = trim(trim($configitem[1], "'"));
	}
}
