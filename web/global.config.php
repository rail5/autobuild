<?php
/**
 * autobuild-web
 * Copyright (C) 2024 rail5
 * This is free software (GNU Affero GPL v3), and you are permitted to redistribute it under certain conditions
 * Please see the LICENSE file for more information
 */

/* Config access functions */
$config_file			= "/var/autobuild/config.toml";
$build_farm_directory	= "/var/autobuild/build-farm";
$repository_directory	= "/var/autobuild/repo";

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
		&& isset($config_data["github"]["email"])
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

	if (vm_is_installing($arch)) {
		return false;
	}

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

function vm_is_installing($arch) {
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
	$file_to_check .= "installing";

	return file_exists($file_to_check);
}

function vm_is_upgrading($arch) {
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
	$file_to_check .= "upgrading";

	return file_exists($file_to_check);
}

function vm_upgrade($arch_list, $redirect = true) {
	global $build_farm_directory;
	foreach ($arch_list as $arch) {
		if (vm_is_upgrading($arch) || !vm_is_configured($arch)) {
			continue;
		}
		run_autobuild("--$arch -u");
	}

	if ($redirect) {
		redirect_and_die("back", array("note" => "upgrading-vm"));
	}
}

function vm_install($arch_list) {
	global $build_farm_directory;
	$log_file = escapeshellarg(create_log_file());
	foreach ($arch_list as $arch) {
		if (vm_is_installing($arch) || vm_is_configured($arch) || vm_is_upgrading($arch)) {
			continue;
		}
		run_autobuild("--$arch -i");
	}
	redirect_and_die("back", array("note" => "installing-vm"));
}

function vm_uninstall($arch_list) {
	global $build_farm_directory;
	foreach ($arch_list as $arch) {
		if (!vm_is_configured($arch) || vm_is_installing($arch) || vm_is_upgrading($arch)) {
			continue;
		}

		$arch_directory = "$build_farm_directory/";
		switch ($arch) {
			case "amd64":
				$arch_directory .= "debian-stable-amd64/";
				break;
			case "i386":
				$arch_directory .= "debian-stable-i386/";
				break;
			case "arm64":
				$arch_directory .= "debian-stable-arm64/";
				break;
		}
		
		unlink($arch_directory."image.qcow");
		unlink($arch_directory."preseed.cfg");
		redirect_and_die("back", array("note" => "removed-vm"));
	}
}

function get_upgrades_to_run($older_than) {
	global $build_farm_directory;
	$arch_list = array("amd64", "i386", "arm64");
	$upgrades = array();

	foreach ($arch_list as $arch) {
		if (vm_is_upgrading($arch) || !vm_is_configured($arch)) {
			continue;
		}

		$arch_directory = "$build_farm_directory/";
		switch ($arch) {
			case "amd64":
				$arch_directory .= "debian-stable-amd64/";
				break;
			case "i386":
				$arch_directory .= "debian-stable-i386/";
				break;
			case "arm64":
				$arch_directory .= "debian-stable-arm64/";
				break;
		}

		$last_upgrade = filemtime($arch_directory."image.qcow");
		if ((time() - $last_upgrade) > ($older_than * 60)) {
			$upgrades[] = $arch;
		}
	}

	return $upgrades;
}

function get_debian_repos() {
	global $repository_directory;

	$repo_folders = array_filter(glob('/var/autobuild/repo/*'), 'is_dir');
	$debian_repos = array();

	foreach ($repo_folders as $repo_folder) {
		if (file_exists($repo_folder."/autobuild_repo.conf")) {
			$debian_repos[] = basename($repo_folder);
		}
	}

	return $debian_repos;
}

function create_debian_repo($repo_name, $repo_url, $signing_key, $github_pages, $github_pages_url) {
	global $repository_directory;

	if ($github_pages && !github_is_configured()) {
		$_GET["error"] = "github-not-configured";
		redirect_and_die("back", $_GET);
	}

	$debian_repos = get_debian_repos();

	if (in_array($repo_name, $debian_repos)) {
		$_GET["error"] = "repo-exists";
		redirect_and_die("back", $_GET);
	}

	$repo_folder = $repository_directory."/".$repo_name;

	$signing_key_fingerprint = get_signing_key_fingerprint($signing_key);


	// Create the repository folder
	mkdir($repo_folder, 0755);

	if ($github_pages) {
		// Prepare the GitHub Pages repository
		$config = parse_config();
		$escaped_repo_folder = escapeshellarg($repo_folder);
		$escaped_github_email = escapeshellarg($config["github"]["email"]);
		$escaped_github_username = escapeshellarg($config["github"]["repo_owner"]);
		$escaped_github_url = escapeshellarg($github_pages_url);
		$pushpull_url = str_replace("://", "://".$config["github"]["repo_owner"].":".$config["github"]["access_token"]."@", $github_pages_url);
		$escaped_pushpull_url = escapeshellarg($pushpull_url);
		`cd $escaped_repo_folder; git init; git config user.email $escaped_github_email; git config user.name $escaped_github_username; git remote add origin $escaped_github_url; git pull $escaped_pushpull_url -q`;
	}

	mkdir("$repo_folder/conf", 0755);


	// Prepare the new repository for reprepro
	$conf_distributions_content = "Origin: $repo_name".PHP_EOL;
	$conf_distributions_content .= "Label: $repo_name".PHP_EOL;
	$conf_distributions_content .= "Codename: unstable".PHP_EOL;
	$conf_distributions_content .= "Architectures: source amd64 i386 arm64".PHP_EOL;
	$conf_distributions_content .= "Components: main".PHP_EOL;
	$conf_distributions_content .= "Description: $repo_name".PHP_EOL;
	$conf_distributions_content .= "SignWith: $signing_key_fingerprint".PHP_EOL;
	$conf_distributions_content .= "Contents: .gz".PHP_EOL;

	file_put_contents($repo_folder."/conf/distributions", $conf_distributions_content);


	// Export the public key
	$public_key_file = "$repo_folder/$repo_name-signing-key.gpg";

	$escaped_public_key_file = escapeshellarg($public_key_file);

	`gpg --export $signing_key_fingerprint > $escaped_public_key_file`;


	// Create the repo .list file
	$list_file_contents = "deb $repo_url unstable main".PHP_EOL;
	$list_file_contents .= "deb-src $repo_url unstable main".PHP_EOL;

	file_put_contents("$repo_folder/$repo_name.list", $list_file_contents);


	// Create the default index.html file for the repo from our template
	$default_index_html_contents = file_get_contents("/usr/share/autobuild/repository/default-index.html");
	$default_index_html_contents = str_replace("%REPO_FRIENDLYNAME%", $repo_name, $default_index_html_contents);
	$default_index_html_contents = str_replace("%REPO_URL%", $repo_url, $default_index_html_contents);

	file_put_contents("$repo_folder/index.html", $default_index_html_contents);


	// Create the autobuild_repo.conf file
	$repo_conf_contents = "[repo]".PHP_EOL;
	if ($github_pages) {
		$repo_conf_contents .= "ghpages = true".PHP_EOL;
		$repo_conf_contents .= "ghpages_url = \"$github_pages_url\"".PHP_EOL;
	} else {
		$repo_conf_contents .= "ghpages = false".PHP_EOL;
	}

	file_put_contents("$repo_folder/autobuild_repo.conf", $repo_conf_contents);

	if ($github_pages) {
		// Push changes to the GitHub Pages repository
		`cd $escaped_repo_folder; git pull $escaped_pushpull_url -q; git add --all; git commit -m "Initialized Autobuild GitHub Pages Debian Repository"; git branch -M main`;
		`cd $escaped_repo_folder; git push $escaped_pushpull_url --all`;
	}
}

function delete_debian_repo($repo) {
	global $repository_directory;
	$valid_repos = get_debian_repos();
	if (!in_array($repo, $valid_repos)) {
		redirect_and_die("back", array("error" => "invalid-repo"));
	}

	remove_directory("$repository_directory/$repo");
}

function delete_debian_repos($repo_list) {
	$valid_repos = get_debian_repos();
	foreach ($repo_list as $repo) {
		if (!in_array($repo, $valid_repos)) {
			redirect_and_die("back", array("error" => "invalid-repo"));
		}
	}

	foreach ($repo_list as $repo) {
		delete_debian_repo($repo);
	}
}

function get_signing_keys($field = "all") {
	$keys = `gpg --list-secret-keys --with-colons | awk -F: '$1=="uid" {print $10}'`;

	if (empty($keys)) {
		return array();
	}

	$keys = explode(PHP_EOL, $keys);

	$key_data = array();

	$i = 0;
	foreach ($keys as $key) {
		if (empty($key)) {
			continue;
		}

		$name = htmlentities(substr($key, 0, strpos($key,"<") - 1));
		$email = strtolower(htmlentities(substr($key, strpos($key,"<") + 1, -1)));

		switch ($field) {
			case "all":
				$key_data[$i]["name"] = $name;
				$key_data[$i]["email"] = $email;
				break;
			case "name":
				$key_data[$i] = $name;
				break;
			case "email":
				$key_data[$i] = $email;
				break;
		}
		$i++;
	}

	return $key_data;
}

function get_signing_key_fingerprint($email) {
	$signing_keys = get_signing_keys("email");

	if (!in_array(strtolower($email), $signing_keys)) {
		return "";
	}

	if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
		return "";
	}

	$escaped_email = escapeshellarg($email);

	// Now get the key fingerprint and return it
	$fingerprint = trim(`gpg -K --with-colons $escaped_email | awk -F: '$1=="fpr" {print $10}' | head -n 1`);

	return $fingerprint;
}

function create_signing_key($name, $email) {
	$signing_keys = get_signing_keys("email");

	if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
		$_GET["error"] = "invalid-email";
		redirect_and_die("back", $_GET);
	}

	if (in_array(strtolower($email), $signing_keys)) {
		$_GET["error"] = "key-exists";
		redirect_and_die("back", $_GET);
	}

	$escaped_email = escapeshellarg(filter_var($email, FILTER_SANITIZE_EMAIL));
	$escaped_name = escapeshellarg(preg_replace("/[^a-zA-Z0-9\ \-\_\.]/", "", $name));

	run_autobuild_and_wait_for_finish("-C -E $escaped_email -N $escaped_name");
}
