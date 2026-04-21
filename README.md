# isp360
Internet Service Provider Resource Planning and Management Application
- Author: Mostafa Joy
- Author-URL: https://www.linkedin.com/in/joyoares

## Local Setup

1. Copy `.env.example` to `.env`.
2. Set database values in `.env`.
3. Run the app from your local web server.

## Live Test Hosting Sync

Use the deployment guide in `DEPLOYMENT.md`.

Daily sync command (from local machine):

```powershell
./scripts/sync-to-server.ps1 -RemoteHost "example.com" -RemoteUser "deploy" -RemotePath "/home/deploy/isp360" -Branch "main" -RunSeeder
```