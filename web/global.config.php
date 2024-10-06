<?php
/* Config access functions */
$config_file = "/var/autobuild/config.toml";
$build_farm_directory = "/var/autobuild/build-farm";

function parse_config() {
	global $config_file;
	$shell_escaped_config_file = escapeshellarg($config_file);
	$parsed = json_decode(`toml2json $shell_escaped_config_file`, true);
	return $parsed;
}

function update_config($new_config) {
	global $config_file;

	$new_config_contents = "# Autobuild configuration";
	$new_config_contents .= PHP_EOL.PHP_EOL;
	$new_config_contents .= "[packages]";
	$new_config_contents .= PHP_EOL;
	$new_config_contents .= "package_urls = [".PHP_EOL;

	/* Parse packages */
	for ($i = 0; $i < count($new_config["packages"]) - 1; $i++) {
		$new_config_contents .= "\t".'"'.$new_config["packages"][$i].'",'.PHP_EOL;
	}
	$new_config_contents .= "\t".'"'.$new_config["packages"][count($new_config["packages"]) - 1].'"'.PHP_EOL;
	$new_config_contents .= "]";

	$new_config_contents .= PHP_EOL.PHP_EOL;

	/* Parse GitHub settings */
	$new_config_contents .= "[github]".PHP_EOL;
	$new_config_contents .= "# Owner username".PHP_EOL;
	$new_config_contents .= 'repo_owner = "'.$new_config["github"]["repo_owner"].'"'.PHP_EOL.PHP_EOL;

	$new_config_contents .= "# Github Email".PHP_EOL;
	$new_config_contents .= 'email = "'.$new_config["github"]["email"].'"'.PHP_EOL;

	$new_config_contents .= "# Access token".PHP_EOL;
	$new_config_contents .= 'access_token = "'.$new_config["github"]["access_token"].'"'.PHP_EOL;

	$new_config_contents .= PHP_EOL;

	/* Parse Forgejo settings */
	$new_config_contents .= "[forgejo]".PHP_EOL;
	$new_config_contents .= "# The distribution settings assume that the repository names are the same across github/forgejo/etc".PHP_EOL;
	$new_config_contents .= PHP_EOL;

	$new_config_contents .= "# Location of your forgejo instance".PHP_EOL;
	$new_config_contents .= 'instance_url = "'.$new_config["forgejo"]["instance_url"].'"'.PHP_EOL;

	$new_config_contents .= PHP_EOL;

	$new_config_contents .= "# Owner username".PHP_EOL;
	$new_config_contents .= 'repo_owner = "'.$new_config["forgejo"]["repo_owner"].'"'.PHP_EOL;

	$new_config_contents .= PHP_EOL;

	$new_config_contents .= "# Access token".PHP_EOL;
	$new_config_contents .= 'access_token = "'.$new_config["forgejo"]["access_token"].'"'.PHP_EOL;

	file_put_contents($config_file, $new_config_contents);
}

function github_is_configured($config_data = 0) {
	if ($config_data == 0) {
		$config_data = parse_config();
	}

	return isset($config_data["github"]["repo_owner"])
		&& isset($config_data["github"]["access_token"]);
}

function forgejo_is_configured($config_data = 0) {
	if ($config_data == 0) {
		$config_data = parse_config();
	}

	return isset($config_data["forgejo"]["instance_url"])
		&& isset($config_data["forgejo"]["repo_owner"])
		&& isset($config_data["forgejo"]["access_token"]);
}

function vm_is_configured($arch) {
	global $build_farm_directory;
	$file_to_check = "$build_farm_directory/";
	switch ($arch) {
		case "amd64":
			$file_to_check .= "debian-stable-amd64/";
			break;
		case "i386":
			$file_to_check .= "debian-stable-i386/";
			break;
		case "arm64":
			$file_to_check .= "debian-stable-arm64/";
			break;
	}
	$file_to_check .= "image.qcow";

	return file_exists($file_to_check);
}
