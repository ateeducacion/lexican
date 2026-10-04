# syntax=docker/dockerfile:1
# Production image: Fastify API + built SPA on one origin. Build: docker build -t lexican .

FROM node:24-bookworm-slim AS build
WORKDIR /app
COPY . .
RUN npm ci --ignore-scripts --no-audit --no-fund \
 && npm run build --workspace @lexican/web \
 && npm run build --workspace @lexican/api

FROM node:24-bookworm-slim AS deps
WORKDIR /app
# Workspace manifests only: npm needs them to resolve the lockfile; sources are already bundled into dist.
COPY package.json package-lock.json ./
COPY apps/api/package.json apps/api/
COPY apps/web/package.json apps/web/
COPY packages/core/package.json packages/core/
COPY packages/app/package.json packages/app/
COPY packages/db/package.json packages/db/
COPY tools/legacy-migrator/package.json tools/legacy-migrator/
RUN npm ci --omit=dev --ignore-scripts --no-audit --no-fund --workspace @lexican/api \
 && npm cache clean --force

FROM node:24-bookworm-slim AS runtime
ENV NODE_ENV=production \
    PORT=3000 \
    MEDIA_DIR=/data/media \
    WEB_DIST=/app/apps/web/dist
WORKDIR /app
COPY --from=deps /app/node_modules ./node_modules
COPY --from=deps /app/package.json ./
COPY --from=deps /app/apps/api/package.json ./apps/api/
COPY --from=build /app/apps/api/dist ./apps/api/dist
COPY --from=build /app/apps/web/dist ./apps/web/dist
RUN mkdir -p /data/media && chown node:node /data/media
USER node
VOLUME ["/data/media"]
EXPOSE 3000
HEALTHCHECK --interval=30s --timeout=5s --start-period=20s --retries=3 \
  CMD ["node", "-e", "fetch('http://127.0.0.1:'+(process.env.PORT||3000)+'/api/health').then(r=>process.exit(r.ok?0:1),()=>process.exit(1))"]
# Migrations: docker run --rm <env> lexican node apps/api/dist/cli/migrate.js  (or MIGRATE_ON_START=true)
CMD ["node", "apps/api/dist/server.js"]
