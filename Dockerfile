# syntax=docker/dockerfile:1
# LexiCán production image: Hono API + built SPA on one origin, run by Bun 1.4.2. Build: docker build -t lexican .
# Configuration comes only from the environment (see apps/api/.env.example and docs/DEPLOYMENT.md). Without it the
# server refuses to start: APP_ENV defaults to production, which requires the institutional CAS + CAUCE.

# Build: Bun installs the workspaces and runs Vite; `bun build` produces the self-contained API bundle.
FROM oven/bun:1.4.2-alpine@sha256:d888c0ae6c86d7866ff10c5aafdd9077b36aee6455b33dd270fb93c0dd5cef6f AS build
WORKDIR /app
COPY . .
RUN bun ci --ignore-scripts \
 && bun run --filter @lexican/web build \
 && bun run --filter @lexican/api build

# Runtime: Bun only. The API bundle includes its dependencies, so there is no node_modules in the image.
FROM oven/bun:1.4.2-alpine@sha256:d888c0ae6c86d7866ff10c5aafdd9077b36aee6455b33dd270fb93c0dd5cef6f AS runtime
LABEL org.opencontainers.image.title="LexiCán" \
    org.opencontainers.image.description="Diccionarios personales y de aula para aprender léxico" \
    org.opencontainers.image.licenses="AGPL-3.0-or-later"
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
