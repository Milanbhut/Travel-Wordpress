# Deploy the wp-authority-builder skill to the user-level Claude skills directory,
# so it is invocable from any folder. The heavy engine (scripts, venv, theme, config)
# stays in this repo; SKILL.md points back here.
$src  = "D:\1Agent1\wp-authority-builder"
$dest = Join-Path $env:USERPROFILE ".claude\skills\wp-authority-builder"
New-Item -ItemType Directory -Force -Path $dest, (Join-Path $dest "references") | Out-Null
Copy-Item (Join-Path $src "SKILL.md") (Join-Path $dest "SKILL.md") -Force
Copy-Item (Join-Path $src "references\*") (Join-Path $dest "references\") -Force
Write-Output "Deployed wp-authority-builder skill to: $dest"
Get-ChildItem $dest -Recurse -File | ForEach-Object { "  " + $_.FullName.Substring($dest.Length + 1) }
