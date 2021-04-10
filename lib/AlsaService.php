<?php 
class AlsaService
{
	public function configure_alsa_mixers()
	{
		foreach (ConfigService::current()->device['amixer'] ?? [] as $amixer_entry)
		{
			$cmd = "amixer -c ".escapeshellarg($amixer_entry['card'])." cset name=".escapeshellarg($amixer_entry['name'])." " . escapeshellarg($amixer_entry['value']);
			`$cmd 2>&1`;
		}
	}
}