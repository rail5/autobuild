<?php

// Variables:

$appname = "BookThief";
$version = "10.0";
$publisher = "rail5";
$homepage = "https://rail5.org/bookthief.html";
$exename = "bookthief.exe";

$rootpath = "./";

// Startup:

$longopts = array();
$shortopts = "v:p:b";

$options = getopt($shortopts, $longopts);

if (!isset($options["b"])) {
	echo 'Error: Specify -b to build BookThief+Liesel';
	die();
}

if (isset($options["v"])) {
	$version = $options["v"];
}

if (isset($options["p"])) {
	$rootpath = $options["p"];
}

$thescriptitself = "#define MyAppName \"$appname\"
#define MyAppVersion \"$version\"
#define MyAppPublisher \"$publisher\"
#define MyAppURL \"$homepage\"
#define MyAppExeName \"$exename\"

[Setup]
; NOTE: The value of AppId uniquely identifies this application. Do not use the same AppId value in installers for other applications.
; (To generate a new GUID, click Tools | Generate GUID inside the IDE.)
AppId={{933E2B3B-2024-44E9-9262-8D2468676F6C}
AppName={#MyAppName}
AppVersion={#MyAppVersion}
;AppVerName={#MyAppName} {#MyAppVersion}
AppPublisher={#MyAppPublisher}
AppPublisherURL={#MyAppURL}
AppSupportURL={#MyAppURL}
AppUpdatesURL={#MyAppURL}
DefaultDirName=C:\Program Files\{#MyAppName}
DisableProgramGroupPage=yes
LicenseFile=Z:$rootpath\pkg\LICENSE.txt
; Uncomment the following line to run in non administrative install mode (install for current user only.)
;PrivilegesRequired=lowest
OutputDir=Z:$rootpath\pkg
OutputBaseFilename=BookThief-$version-Installer
Compression=lzma
SolidCompression=yes
WizardStyle=modern

[Languages]
Name: \"english\"; MessagesFile: \"compiler:Default.isl\"

[Tasks]
Name: \"desktopicon\"; Description: \"{cm:CreateDesktopIcon}\"; GroupDescription: \"{cm:AdditionalIcons}\"; Flags: unchecked

[Files]
Source: \"Z:$rootpath\pkg\{#MyAppExeName}\"; DestDir: \"{app}\"; Flags: ignoreversion
Source: \"Z:$rootpath\pkg\liesel.exe\"; DestDir: \"{app}\"; Flags: ignoreversion
Source: \"Z:$rootpath\source\*\"; DestDir: \"{app}\"; Flags: ignoreversion recursesubdirs createallsubdirs
; NOTE: Don't use \"Flags: ignoreversion\" on any shared system files

[Icons]
Name: \"{autoprograms}\{#MyAppName}\"; Filename: \"{app}\{#MyAppExeName}\"
Name: \"{autodesktop}\{#MyAppName}\"; Filename: \"{app}\{#MyAppExeName}\"; Tasks: desktopicon

[Run]
Filename: \"{app}\{#MyAppExeName}\"; Description: \"{cm:LaunchProgram,{#StringChange(MyAppName, '&', '&&')}}\"; Flags: nowait postinstall skipifsilent

";

echo $thescriptitself;
?>
