@echo off
cd /d "%~dp0"
echo Tourky VPS deploy - run AFTER: ssh root@69.62.118.54 works
echo.
powershell -NoProfile -ExecutionPolicy Bypass -Command ^
  "$p = Read-Host 'Root password (hidden)'; $sec = ConvertTo-SecureString $p -AsPlainText -Force; $bstr = [Runtime.InteropServices.Marshal]::SecureStringToBSTR($sec); $plain = [Runtime.InteropServices.Marshal]::PtrToStringAuto($bstr); .\windows-deploy.ps1 -SshPassword $plain"
pause
