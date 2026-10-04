# syntax=docker/dockerfile:1
# LexiCán production image: Hono API + built SPA on one origin, run by Bun 1.4.2. Build: docker build -t lexican .
# Configuration comes only from the environment (see apps/api/.env.example and docs/DEPLOYMENT.md). Without it the
# server refuses to start: APP_ENV defaults to production, which requires the institutional CAS + CAUCE.

# Build: npm workspaces + Vite (Node) for the web bundle, `bun build` for the self-contained API bundle.
FROM node:24-alpine@sha256:ebfe2f90462722a7a4de65e91990e97fe0d401c70e0e762c5b53302f905ec1c1 AS build
COPY --from=oven/bun:1.4.2-alpine@sha256:d888c0ae6c86d7866ff10c5aafdd9077b36aee6455b33dd270fb93c0dd5cef6f /usr/local/bin/bun /usr/local/bin/bun
WORKDIR /app
COPY . .
RUN npm ci --ignore-scripts --no-audit --no-fund \
 && npm run build --workspace @lexican/web \
 && npm run build --workspace @lexican/api

# Runtime: Bun only. The API bundle includes its dependencies, so there is no node_modules in the image.
FROM oven/bun:1.4.2-alpine@sha256:d888c0ae6c86d7866ff10c5aafdd9077b36aee6455b33dd270fb93c0dd5cef6f AS runtime
ENV APP_ENV=production \
    PORT=3000 \
    MEDIA_DIR=/data/media \
    WEB_DIST=/app/web \
    DEMO_ASSETS_DIR=/app/web/demo
WORKDIR /app
COPY --from=build /app/apps/api/dist ./api
COPY --from=build /app/apps/web/dist ./web
RUN mkdir -p /data/media && chown bun:bun /data/media
USER bun
VOLUME ["/data/media"]
EXPOSE 3000
HEALTHCHECK --interval=30s --timeout=5s --start-period=20s --retries=3 \
  CMD ["bun", "--no-env-file", "-e", "fetch('http://127.0.0.1:'+(process.env.PORT||3000)+'/api/health').then(r=>process.exit(r.ok?0:1),()=>process.exit(1))"]
# Migrations: `docker run --rm <env> lexican bun --no-env-file api/cli/migrate.js` (or MIGRATE_ON_START=true).
# Bun forwards SIGTERM to the server, which stops accepting requests, drains them and closes the pool.
CMD ["bun", "--no-env-file", "api/server.js"]
