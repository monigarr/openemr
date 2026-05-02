# OpenEMR from this repository for Railway and similar hosts (build context = repo root).
# Intended branch: prd_1_agentforge_monigarr (checkout that branch before `docker build`, or set the
# same branch in your Railway service so the build context includes custom modules under
# interface/modules/custom_modules/).
#
# hadolint ignore=DL3008,DL3015
FROM openemr/openemr:flex

# Flex is Alpine-based; root required for package installs (matches flex runtime expectations).
# hadolint ignore=DL3002
USER root

# Node 24 + build toolchain for npm/gulp and ccdaservice native deps (e.g. libxmljs2).
# hadolint ignore=DL3018
RUN apk update \
    && apk add --no-cache \
        g++ \
        git \
        libxml2-dev \
        linux-headers \
        make \
        nodejs \
        npm \
        python3 \
    && rm -rf /var/cache/apk/*

WORKDIR /var/www/localhost/htdocs/openemr

COPY . .

ENV COMPOSER_ALLOW_SUPERUSER=1

RUN composer install --no-dev --no-interaction --prefer-dist \
    && npm ci \
    && npm run build \
    && (cd ccdaservice && npm ci) \
    && composer dump-autoload -o \
    && rm -rf /root/.composer/cache /tmp/* /var/tmp/*

ARG SOURCE_BRANCH=prd_1_agentforge_monigarr
ARG GIT_COMMIT=unknown
LABEL org.opencontainers.image.title="OpenEMR (custom branch for deploy)" \
      org.opencontainers.image.source="https://github.com/openemr/openemr" \
      org.opencontainers.image.version="${SOURCE_BRANCH}" \
      org.opencontainers.image.revision="${GIT_COMMIT}"

EXPOSE 80 443
