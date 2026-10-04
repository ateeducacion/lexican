# Thin wrapper over npm scripts (package.json is the source of truth).
.DEFAULT_GOAL := help
.PHONY: up down dev demo build build-demo test integration e2e lint fix check migrate seed clean help

## Start local PostgreSQL (docker compose)
up:
	docker compose up -d db

## Stop local services
down:
	docker compose down

## Web (Vite) against the local API; run `npm run dev:api` in another terminal
dev:
	npm run dev

## Demo mode (PGlite in the browser, no backend)
demo:
	npm run dev:demo

## Production build (web + API)
build:
	npm run build

## Static GitHub Pages demo build
build-demo:
	npm run build:demo

## Unit + contract tests (PostgreSQL too when TEST_DATABASE_URL is set)
test:
	npm test

## API integration tests
integration:
	npm run test:integration

## End-to-end tests (Playwright)
e2e:
	npm run e2e

## Lint and typecheck
lint:
	npm run lint && npm run typecheck

## Fix lint and formatting
fix:
	npx eslint . --fix && npm run format

## Full local quality gate
check:
	npm run check

## Apply database migrations (DATABASE_URL)
migrate:
	npm run db:migrate

## Seed vocabularies (and demo data with ARGS=--demo)
seed:
	npm run db:seed -- $(ARGS)

## Remove build output
clean:
	rm -rf apps/*/dist apps/web/dist-demo coverage playwright-report test-results

## Show this help
help:
	@awk '/^## /{h=substr($$0,4)} /^[a-z-]+:/{if(h){sub(":.*","",$$1); printf "  \033[1m%-12s\033[0m %s\n",$$1,h; h=""}}' $(MAKEFILE_LIST)
