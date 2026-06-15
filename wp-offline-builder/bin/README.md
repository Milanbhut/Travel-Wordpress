# bin/

Drop the WP-CLI Phar here so the offline agent can drive WordPress under XAMPP.

## What goes here

- `wp-cli.phar` - the WP-CLI executable. **Not committed** (it's a binary blob); download it once.

## Download (one time)

Save it as `wp-cli.phar` in this folder:

```
https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar
```

PowerShell:

```powershell
Invoke-WebRequest `
  -Uri "https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar" `
  -OutFile "D:\1Agent1\wp-offline-builder\bin\wp-cli.phar"
```

## How it's used

The agent runs it with XAMPP's PHP, e.g.:

```
C:\xampp\php\php.exe D:\1Agent1\wp-offline-builder\bin\wp-cli.phar --path=C:\xampp\htdocs\<slug> core version
```

The paths come from `config/secrets.env` (`LOCAL_PHP`, `WP_CLI_PHAR`, `WP_PATH`). No global
`wp` install is required - everything runs from this Phar.

## Verify

```powershell
C:\xampp\php\php.exe D:\1Agent1\wp-offline-builder\bin\wp-cli.phar --info
```

You should see `WP-CLI` version output.
