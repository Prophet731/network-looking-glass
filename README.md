# Looking Glass

A modern looking glass for network diagnostics. Built on Laravel 10, Vue 3, and Tailwind CSS.

# :exclamation: Disclaimer :exclamation:

This is still a work in progress. It is not ready for production use. If you would like to contribute, please feel free 
to open a pull request.

# TODO

- [ ] Add support for IPv6 on traceroute (semi working).
- [ ] Add speedtest support.
- [ ] Add support for multiple looking glass servers.
- [ ] Add iperf support.
- [ ] Add map view for traceroute.
- [ ] Implement websocket support for all commands for real time output.
- [ ] Find a fix for the MTR command while inside a container. It does not show all hops.
- [ ] Create installer script.
- [ ] Store results in database for historical data and analytics. 
- [ ] Allow support for sharing results.

# Requirements for building from source

- PHP 8.1+
- Composer
- Node.js 20+
- NPM 10+

# Getting Started

```bash
mkdir -p /var/www/looking-glass && \
cd /var/www/looking-glass && \
git clone https://github.com/Prophet731/network-looking-glass.git . && \
composer install && \
npm install && \
npm run build && \
cp .env.example .env && \
$(which php) artisan key:generate
```

# Configuration

See the `.env` file for configuration options.

# Preview

![Preview](/docs/images/screenshot.jpeg)
