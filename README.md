# Fake SteamPulse

**Fake SteamPulse** is a hobby Telegram bot project inspired by the [SteamPulse_Web](https://github.com/CodeMageIR/SteamPulse_Web) project. It provides live Team Fortress 2 (TF2) Key and Tour of Duty Ticket prices for multiple regions by querying the Steam Community Market.

## Features

- Live **TF2 Key** and **Tour of Duty Ticket** prices.
- Supports multiple regions and currencies: USA, UK, Europe Argentina, Turkey, Ukraine, Russia, India, Brazil, Kazakhstan. Norway, China, Japan.
- Rich Telegram messages using Telegram RichMessage HTML.
- Inline buttons for navigation and instant price refresh.
- Automatic price caching to reduce Steam Market requests.
- Region-specific currency formatting.
- Built-in admin tools for diagnostics, health checks, and testing.

## User Commands

- `/start` — Show the main menu.
- `/help` — Show the main menu.
- `/about` — Display project information.

## Admin Commands

- `/admin` — List all admin commands.
- `/health` — Check Telegram API, Steam Market, cache, and cooldown status.
- `/stats` — Show bot statistics.
- `/cache` — View cache statistics.
- `/cacheclear` — Clear the price cache.
- `/debug` — Dump cache files.
- `/testprice` — Test default Steam Market request.
- `/testprice key usd`
- `/testprice ticket eur`
- `/testrich` — Preview generated RichMessage HTML.
- `/testrich key`
- `/testrich ticket`

## Deployment

The bot is ready to deploy on **Render.com** or any PHP 8.1+ environment.

### Environment Variables

- `TELEGRAM_BOT_TOKEN` — Telegram Bot API token.
- `ADMIN_ID` — Telegram User ID with admin access.
- `DEBUG_TOKEN` — Debug endpoint token (optional).

### Docker

```bash
docker build -t fakesteampulse .
docker run -d \
  -e TELEGRAM_BOT_TOKEN=<YOUR_BOT_TOKEN> \
  -e ADMIN_ID=<YOUR_ADMIN_ID> \
  fakesteampulse
```

### Webhook

After deploying, register your webhook:

```
https://api.telegram.org/bot<YOUR_BOT_TOKEN>/setWebhook?url=<YOUR_URL>
```

Replace:

- `<YOUR_BOT_TOKEN>` with your bot token.
- `<YOUR_URL>` with your public HTTPS endpoint.

## Credits

Inspired by the original [SteamPulse_Web](https://github.com/CodeMageIR/SteamPulse_Web) project by CodeMageIR.

## License

This project is licensed under the **GNU General Public License v3.0 (GPL-3.0)**.
