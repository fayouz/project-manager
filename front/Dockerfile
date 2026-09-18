#syntax=docker/dockerfile:1

# Versions
FROM node:20-alpine AS node_upstream

# Base stage for dev and build
FROM node_upstream AS base

RUN apk add --no-cache libc6-compat

WORKDIR /srv/app

RUN npm install -g pnpm@9 && \
	pnpm config -g set store-dir /.pnpm-store

# Development image
FROM base AS dev

EXPOSE 3000
ENV PORT 3000
ENV HOSTNAME 0.0.0.0

CMD ["sh", "-c", "pnpm install; pnpm dev --host"]

FROM base AS builder

COPY --link pnpm-lock.yaml* ./
RUN if [ -f pnpm-lock.yaml ]; then pnpm fetch; fi

COPY --link . .

RUN	pnpm install && \
	pnpm run build


# Production image
FROM node_upstream AS prod

WORKDIR /srv/app

ENV NODE_ENV production

RUN addgroup --system --gid 1001 nodejs; \
	adduser --system --uid 1001 nuxtjs

COPY --from=builder --link --chown=1001:1001 /srv/app/.output ./

USER nuxtjs

EXPOSE 3000

ENV PORT 3000
ENV HOSTNAME "0.0.0.0"

CMD ["node", "server/index.mjs"]
