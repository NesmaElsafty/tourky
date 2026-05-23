# Tourky VPS deploy

## If deploy from Cursor/CI fails (port 22 timeout)

Hostinger firewall or your network may block SSH from some IPs. Open **VPS → Security → Firewall → TCP 22**.

## Quick deploy from Windows

1. DNS: `backend` A record → `89.62.118.54`
2. Push latest code to GitHub (`dev` branch)
3. PowerShell:

```powershell
cd D:\Projects\tourky\deploy
.\windows-deploy.ps1 -SshPassword 'YOUR_ROOT_PASSWORD'
```

With SMTP:

```powershell
.\windows-deploy.ps1 -SshPassword 'YOUR_ROOT_PASSWORD' -MailPassword 'YOUR_SMTP_PASSWORD'
```

4. Read credentials on server:

```bash
cat /root/tourky-deploy-credentials.txt
```

5. **Change root password** in Hostinger hPanel.

## Manual (paste on server as root)

```bash
curl -sL https://raw.githubusercontent.com/NesmaElsafty/tourky/dev/deploy/vps-install.sh -o /root/vps-install.sh
# Or upload deploy/vps-install.sh via SFTP
chmod +x /root/vps-install.sh
export DOMAIN_API=backend.tourkygroup.com
bash /root/vps-install.sh
```
