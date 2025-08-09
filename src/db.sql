BEGIN TRANSACTION;

CREATE TABLE IF NOT EXISTS "Users" (
	"uid"	INTEGER NOT NULL UNIQUE,
	"username"	TEXT NOT NULL UNIQUE,
	"password"	TEXT NOT NULL,
	"privilege_level"	TEXT NOT NULL CHECK("privilege_level" IN ('user', 'mod', 'admin')),
	PRIMARY KEY("uid" AUTOINCREMENT)
);

CREATE TABLE IF NOT EXISTS "SigningKeys" (
	"owned_by"	INTEGER NOT NULL,
	"email"	TEXT NOT NULL UNIQUE,
	FOREIGN KEY("owned_by") REFERENCES "Users"("uid") ON DELETE CASCADE,
	PRIMARY KEY("owned_by","email")
);

CREATE TABLE IF NOT EXISTS "VirtualMachines" (
	"name"	TEXT NOT NULL,
	"operating_system"	TEXT NOT NULL DEFAULT 'debian',
	"architecture"	TEXT NOT NULL DEFAULT 'amd64',
	"img_path"	TEXT NOT NULL UNIQUE,
	"installation_img"	TEXT, -- NULL for built-in system VMs
	"ram"	INTEGER NOT NULL,
	"cpu"	INTEGER NOT NULL,
	"last_upgraded"	INTEGER,
	"accessible_by"	TEXT NOT NULL DEFAULT 'user' CHECK("accessible_by" IN ('user', 'mod', 'admin')),
	PRIMARY KEY("name")
);

CREATE TABLE IF NOT EXISTS "GitRepositories" (
	"id"	INTEGER NOT NULL UNIQUE,
	"owned_by"	INTEGER NOT NULL,
	"instance_url"	TEXT NOT NULL,
	"repo_user"	TEXT NOT NULL,
	"repo_name"	TEXT NOT NULL,
	"branch"	TEXT,
	FOREIGN KEY("owned_by") REFERENCES "Users"("uid") ON DELETE CASCADE,
	UNIQUE("owned_by","instance_url","repo_user","repo_name","branch"),
	PRIMARY KEY("id" AUTOINCREMENT)
);

CREATE TABLE IF NOT EXISTS "ReadOnlyDebianRepositories" (
	"id"	INTEGER NOT NULL UNIQUE,
	"owned_by"	INTEGER NOT NULL,
	"signed_by"	TEXT NOT NULL,
	"sources_list"	TEXT NOT NULL,
	"name"	TEXT NOT NULL,
	FOREIGN KEY("owned_by") REFERENCES "Users"("uid") ON DELETE CASCADE,
	UNIQUE("owned_by","name"),
	PRIMARY KEY("id" AUTOINCREMENT)
);

CREATE TABLE IF NOT EXISTS "GitCredentials" (
	"owned_by"	INTEGER NOT NULL,
	"instance_url"	TEXT NOT NULL,
	"git_username"	TEXT NOT NULL,
	"access_token"	TEXT NOT NULL,
	FOREIGN KEY("owned_by") REFERENCES "Users"("uid") ON DELETE CASCADE,
	PRIMARY KEY("owned_by","instance_url")
);

CREATE TABLE IF NOT EXISTS "PkgExternalDependencies" (
	"id"	INTEGER NOT NULL UNIQUE,
	"for_package"	INTEGER NOT NULL,
	"git_repository"	INTEGER,
	"debian_repository"	INTEGER,
	"readonly_debian_repository"	INTEGER,
	-- XOR: Exactly one dependency type must be set
	CHECK((("git_repository" IS NOT NULL) + ("debian_repository" IS NOT NULL) + ("readonly_debian_repository" IS NOT NULL)) = 1),
	FOREIGN KEY("git_repository") REFERENCES "GitRepositories"("id") ON DELETE CASCADE,
	FOREIGN KEY("for_package") REFERENCES "Packages"("id") ON DELETE CASCADE,
	FOREIGN KEY("debian_repository") REFERENCES "DebianRepositories"("id") ON DELETE CASCADE,
	PRIMARY KEY("id" AUTOINCREMENT),
	FOREIGN KEY("readonly_debian_repository") REFERENCES "ReadOnlyDebianRepositories"("id") ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS "Builds" (
	"id"	TEXT NOT NULL UNIQUE,
	"run_by"	INTEGER NOT NULL,
	"status"	TEXT NOT NULL CHECK("status" IN ('queued', 'in_progress', 'completed', 'failed', 'cancelled')),
	"datetime"	INTEGER,
	PRIMARY KEY("id"),
	FOREIGN KEY("run_by") REFERENCES "Users"("uid") ON DELETE CASCADE
);
CREATE INDEX "idx_builds_datetime" ON "Builds" ("datetime");

CREATE TABLE IF NOT EXISTS "Packages" (
	"id"	INTEGER NOT NULL UNIQUE,
	"owned_by"	INTEGER NOT NULL,
	"name"	TEXT NOT NULL,
	"source_git"	INTEGER,
	"source_debian"	INTEGER,
	"source_readonly_debian"	INTEGER,
	FOREIGN KEY("source_readonly_debian") REFERENCES "ReadOnlyDebianRepositories"("id") ON DELETE RESTRICT,
	-- XOR: Exactly one source channel must be set
	CHECK((("source_git" IS NOT NULL) + ("source_debian" IS NOT NULL) + ("source_readonly_debian" IS NOT NULL)) = 1),
	FOREIGN KEY("owned_by") REFERENCES "Users"("uid") ON DELETE CASCADE,
	FOREIGN KEY("source_git") REFERENCES "GitRepositories"("id") ON DELETE RESTRICT,
	FOREIGN KEY("source_debian") REFERENCES "DebianRepositories"("id") ON DELETE RESTRICT,
	PRIMARY KEY("id" AUTOINCREMENT),
	UNIQUE("name","owned_by")
);

CREATE TABLE IF NOT EXISTS "VMDefaultRepositories" (
	"vm_name"	TEXT NOT NULL,
	"repo_id"	INTEGER NOT NULL,
	PRIMARY KEY("vm_name","repo_id"),
	FOREIGN KEY("repo_id") REFERENCES "ReadOnlyDebianRepositories"("id") ON DELETE RESTRICT,
	FOREIGN KEY("vm_name") REFERENCES "VirtualMachines"("name") ON DELETE CASCADE ON UPDATE CASCADE
);

CREATE TABLE IF NOT EXISTS "DebianRepositories" (
	"id"	INTEGER NOT NULL UNIQUE,
	"owned_by"	INTEGER NOT NULL,
	"name"	TEXT NOT NULL,
	"sign_with"	TEXT NOT NULL,
	"git_repository"	INTEGER UNIQUE,
	PRIMARY KEY("id" AUTOINCREMENT),
	FOREIGN KEY("owned_by") REFERENCES "Users"("uid") ON DELETE CASCADE,
	FOREIGN KEY("sign_with") REFERENCES "SigningKeys"("email") ON DELETE RESTRICT ON UPDATE CASCADE,
	FOREIGN KEY("git_repository") REFERENCES "GitRepositories"("id") ON DELETE CASCADE
);

COMMIT;
