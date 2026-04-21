# ISP360 Deployment & Sync Guide

This setup is optimized for **local development -> online test hosting** using Git.

## 1) One-time local setup

1. Copy `.env.example` to `.env`.
2. Update `.env` with local database credentials.
3. Ensure your local URL is correct (`APP_URL`).

## 2) One-time server setup

1. Create a deployment directory on server (example: `/home/youruser/isp360`).
2. Clone your repository there.
3. Copy `.env.example` to `.env` and set live/test DB credentials.
4. Create upload directory if not present:
   - `mkdir -p public/assets/uploads`
5. Point hosting document root to `.../isp360/public`.
   - If your host cannot point to `/public`, use project root document root; root `index.php` will bridge requests to `public/index.php`.

## 3) Daily sync (local -> server)

Run from local machine after committing changes:

```powershell
./scripts/sync-to-server.ps1 -RemoteHost "example.com" -RemoteUser "deploy" -RemotePath "/home/deploy/isp360" -Branch "main" -RunSeeder
```

What it does:
1. Pushes local branch to `origin`.
2. SSH into server and runs `git pull --ff-only`.
3. Optionally runs `php database/seeder.php`.

## 4) Recommended production-safe checks

1. Keep `.env` out of Git (already ignored).
2. Disable debug on server (`APP_DEBUG=0`).
3. Use non-root DB user with limited privileges.
4. Back up DB before schema changes.

## 5) Quick rollback

On server:

```bash
cd /home/deploy/isp360
git log --oneline -n 5
git checkout <previous_commit_hash>
```

Then re-run any required cache clear or service reload per hosting environment.
