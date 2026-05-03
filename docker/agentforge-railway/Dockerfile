# OpenEMR from this repository for Railway and similar hosts (build context = repo root).
# Railway builds the connected Git branch; `COPY . .` includes custom modules, including
# interface/modules/custom_modules/oe-module-clinical-copilot when present in that branch.
# Default label branch: prd_1_agentforge_monigarr (see README for Dockerfile path / branch setup).
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

RUN rm -f /etc/apache2/conf.d/mpm_event.conf || true

WORKDIR /var/www/localhost/htdocs/openemr
# Flex keeps openemr.sh, ssl.sh, xdebug.sh, pcov.sh, auto_configure.php under /var/www/localhost/htdocs/
# (openemr-devops docker/openemr/flex). COPY . . only replaces this directory — it does not remove those
# scripts. Do not copy openemr.sh into the app tree or leave final WORKDIR as openemr/: openemr.sh runs
# `sh ssl.sh` before any cd; CWD must be htdocs so ssl.sh resolves (avoids "can't open 'ssl.sh'").
COPY . .

ENV COMPOSER_ALLOW_SUPERUSER=1

# One layer: PHP + JS assets + ccdaservice; then drop caches and fix ownership for Apache (flex).
# Do not end a line with `\` before a Dockerfile `RUN` — continuations merge the next line into this shell.
RUN composer install --no-dev --no-interaction --prefer-dist \
    && npm ci \
    && npm run build \
    && (cd ccdaservice && npm ci) \
    && composer dump-autoload -o \
    && rm -rf /root/.composer/cache /tmp/* /var/tmp/* \
    && chown -R apache:apache /var/www/localhost/htdocs/openemr \
    && chmod -R 755 /var/www/localhost/htdocs/openemr

# Inherited CMD is ./openemr.sh — same CWD as stock flex so sibling ssl.sh / auto_configure.php paths work.
WORKDIR /var/www/localhost/htdocs

ARG SOURCE_BRANCH=prd_1_agentforge_monigarr
ARG GIT_COMMIT=unknown
LABEL org.opencontainers.image.title="OpenEMR (AgentForge PRD 1)" \
      org.opencontainers.image.source="https://github.com/openemr/openemr" \
      org.opencontainers.image.version="${SOURCE_BRANCH}" \
      org.opencontainers.image.revision="${GIT_COMMIT}"

EXPOSE 80 443
