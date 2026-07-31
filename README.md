# Winning Heaven (Laravel) — Full platform

Custom-branded rebuild of the Jackpot Royals cashier/lobby/staff/distributor/affiliate system.

**Style:** fresh Fraunces + DM Sans celestial theme (not a Jackpot UI clone)  
**Assets:** original `/public/brand/*` and `/public/games/*` (no Jackpot images)

## Run
```bash
cd ~/Desktop/WinningHeavenLaravel
php artisan migrate --force
php artisan db:seed --force
php artisan serve
```
http://127.0.0.1:8000

## Portals
| URL | Who |
|-----|-----|
| `/login` | Players |
| `/admin/login` | HQ staff (separate) |
| `/admin` | Full HQ desk |
| `/distributor` | Distributors A/B |
| `/affiliate` | Agents / campaigns |
| `/lobby` `/referrals` `/info` | Player flows |

## Demo logins
- Admin: `admin@winningheaven.com` / `admin123`
- Player: `player@winningheaven.com` / `player123`
- Dist: `dist@winningheaven.com` / `dist123`
- Agent: `agent@winningheaven.com` / `agent123`

## Apps (Capacitor — same as original approach)
Point WebView at live site (`WH_SITE_URL`):
- `capacitor.config.ts` — player
- `capacitor.portal.config.ts` — HQ
- `capacitor.distributor.config.ts` — distributor

```bash
npm i @capacitor/core @capacitor/cli @capacitor/android @capacitor/ios
npx cap add android && npx cap add ios
```

## Push
`POST /api/push/subscribe` · `POST /api/push/broadcast`  
Add `VAPID_*` / Firebase env for real delivery.
