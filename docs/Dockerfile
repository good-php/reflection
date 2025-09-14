ARG CONTAINER_ENV=local

FROM node:24.2-alpine AS base

WORKDIR /app
EXPOSE 22 80

COPY docs/package.json /app/package.json
COPY docs/yarn.lock /app/yarn.lock
RUN --mount=type=cache,id=docs-yarn,target=/usr/local/share/.cache/yarn yarn install --frozen-lockfile


FROM base AS base-production

# For Starlight's "last updated" field
COPY --from=alpine/git:2.47.1 /usr/bin/git /usr/bin/git

COPY docs /app/

ARG SITE_URL="http://localhost"
ARG BASE_PREFIX="/"

RUN --mount=type=cache,id=docs-astro,target=/app/.astro yarn build --site "$SITE_URL" --base "$BASE_PREFIX"

CMD ["yarn", "preview"]


FROM base AS base-local

CMD ["yarn", "dev"]


FROM base-${CONTAINER_ENV}
