# Run from YOUR Windows PC (PowerShell), after SSH port 22 works:
#   cd D:\Projects\tourky\deploy
#   .\windows-deploy.ps1 -SshPassword 'YOUR_ROOT_PASSWORD'
#
# Optional: -MailPassword 'smtp-password' -DomainApi 'api.tourkygroup.com'

param(
    [string]$VpsIp = "69.62.118.54",
    [string]$SshUser = "root",
    [Parameter(Mandatory = $true)]
    [string]$SshPassword,
    [string]$DomainApi = "backend.tourkygroup.com",
    [string]$MailPassword = "",
    [string]$GitBranch = "dev"
)

$ErrorActionPreference = "Stop"
$Plink = "C:\Program Files\PuTTY\plink.exe"
$Pscp = "C:\Program Files\PuTTY\pscp.exe"
$ScriptDir = Split-Path -Parent $MyInvocation.MyCommand.Path
$RemoteScript = "/root/vps-install.sh"

if (-not (Test-Path $Plink)) {
    throw "PuTTY plink not found. Install PuTTY or use SSH manually."
}

Write-Host "Testing SSH to ${SshUser}@${VpsIp} ..."
# Accept host key on first connect
"echo OK" | & $Plink -ssh "${SshUser}@${VpsIp}" -pw $SshPassword -batch 2>$null | Out-Null
$test = & $Plink -ssh "${SshUser}@${VpsIp}" -pw $SshPassword -batch "echo OK" 2>&1
if ($LASTEXITCODE -ne 0) {
    Write-Host $test
    throw @"
SSH failed (timeout or refused).

Fix on Hostinger:
1. VPS -> Security -> Firewall -> allow TCP port 22 (from your IP or 0.0.0.0/0 temporarily).
2. Confirm password: Overview -> Root password -> Change.
3. Retry: ssh ${SshUser}@${VpsIp}
"@
}

Write-Host "Uploading install script..."
& $Pscp -pw $SshPassword "$ScriptDir\vps-install.sh" "${SshUser}@${VpsIp}:${RemoteScript}"

$mailEnv = ""
if ($MailPassword) {
    $mailEnv = "MAIL_PASSWORD=$(($MailPassword -replace '"', '\"'))"
}

$remoteCmd = @"
chmod +x ${RemoteScript}
export DOMAIN_API=${DomainApi}
export GIT_BRANCH=${GitBranch}
export ${mailEnv}
bash ${RemoteScript}
"@

Write-Host "Running install (10-20 min)..."
& $Plink -ssh "${SshUser}@${VpsIp}" -pw $SshPassword -batch $remoteCmd

Write-Host ""
Write-Host "Fetch credentials from server:"
Write-Host "  plink -ssh ${SshUser}@${VpsIp} -pw *** -batch `"cat /root/tourky-deploy-credentials.txt`""
Write-Host ""
Write-Host "IMPORTANT: Change root password in Hostinger after deploy."
