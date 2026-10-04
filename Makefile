# Thin wrapper over bun scripts (package.json is the source of truth).
.DEFAULT_GOAL := help

.PHONY: up down app dev demo build build-demo test integration e2e lint fix check migrate seed clean help

## Start local PostgreSQL (docker compose)
up:
	docker compose up -d db

## Local Docker stack (APP_ENV=local): migrate, seed fictitious data, app on http://localhost:3000
app:
	docker compose --profile app up --build -d

## Stop local services
down:
	docker compose --profile app down

## Web (Vite) against the local API; run `bun run dev:api` in another terminal
dev:
	bun run dev

## Demo mode (Hono API + PGlite in a Web Worker, no backend)
demo:
	bun run dev:demo

## Production build (web + API)
build:
	bun run build

## Static GitHub Pages demo build
build-demo:
	bun run build:demo

## Unit + contract tests (PostgreSQL too when TEST_DATABASE_URL is set)
test:
	bun run test

## API integration tests
integration:
	bun run test:integration

## End-to-end tests (Playwright)
e2e:
	bun run e2e

## Lint and typecheck
lint:
	bun run lint && bun run typecheck

## Fix lint and formatting
fix:
	bunx eslint . --fix && bun run format

## Full local quality gate
check:
	bun run check

## Apply database migrations (DATABASE_URL)
migrate:
	bun run db:migrate

## Seed vocabularies (and demo data with ARGS=--demo)
seed:
	bun run db:seed -- $(ARGS)

## Remove build output
clean:
	rm -rf apps/*/dist apps/web/dist-demo coverage playwright-report test-results

## Show this help
help:
	@awk '/^## /{h=substr($$0,4)} /^[a-z-]+:/{if(h){sub(":.*","",$$1); printf "  \033[1m%-12s\033[0m %s\n",$$1,h; h=""}}' $(MAKEFILE_LIST)
