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
# openemr/openemr:flex ships the startup script at /var/www/localhost/htdocs/openemr.sh (not inside openemr/).
# WORKDIR is openemr/, so CMD ./openemr.sh expects ./openemr.sh here. COPY . . replaces only this tree — stash
# the flex script from its real path, restore after copy. Build fails if the base layout changes.
RUN FLEX_ENTRY=/var/www/localhost/htdocs/openemr.sh \
    && test -f "$FLEX_ENTRY" \
    && cp -a "$FLEX_ENTRY" /tmp/openemr.sh.flex
COPY . .
RUN test -f /tmp/openemr.sh.flex \
    && cp -a /tmp/openemr.sh.flex ./openemr.sh \
    && chmod 755 ./openemr.sh \
    && rm -f /tmp/openemr.sh.flex

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

ARG SOURCE_BRANCH=prd_1_agentforge_monigarr
ARG GIT_COMMIT=unknown
LABEL org.opencontainers.image.title="OpenEMR (AgentForge PRD 1)" \
      org.opencontainers.image.source="https://github.com/openemr/openemr" \
      org.opencontainers.image.version="${SOURCE_BRANCH}" \
      org.opencontainers.image.revision="${GIT_COMMIT}"

EXPOSE 80 443
