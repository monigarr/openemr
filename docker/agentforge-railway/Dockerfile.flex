# OpenEMR from this repository for Railway (flex base image). Build context = repo root.
# Prefer `Dockerfile` (production-style, openemr-devops 8.1.1 template) for Railway unless you need flex runtime behavior.
# Default label branch: prd_1_agentforge_monigarr (see README).
#
# hadolint ignore=DL3008,DL3015
FROM openemr/openemr:flex

# hadolint ignore=DL3002
USER root

ENV PORT=80

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
# Flex keeps openemr.sh, ssl.sh, etc. under /var/www/localhost/htdocs/; final WORKDIR must be htdocs (see README).
COPY . .

ENV COMPOSER_ALLOW_SUPERUSER=1

RUN composer install --no-dev --no-interaction --prefer-dist \
    && npm ci \
    && npm run build \
    && (cd ccdaservice && npm ci) \
    && composer dump-autoload -o \
    && rm -rf /root/.composer/cache /tmp/* /var/tmp/* \
    && chown -R apache:apache /var/www/localhost/htdocs/openemr \
    && chmod -R 755 /var/www/localhost/htdocs/openemr

WORKDIR /var/www/localhost/htdocs

ARG SOURCE_BRANCH=prd_1_agentforge_monigarr
ARG GIT_COMMIT=unknown
LABEL org.opencontainers.image.title="OpenEMR (AgentForge flex)" \
      org.opencontainers.image.source="https://github.com/openemr/openemr" \
      org.opencontainers.image.version="${SOURCE_BRANCH}" \
      org.opencontainers.image.revision="${GIT_COMMIT}"

EXPOSE 80 443
