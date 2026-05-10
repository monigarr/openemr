# PRD2 Modernized — production Next.js image for Railway (Track B).
# Version: 0.1.0
# Date: 2026-05-09
# Author: Monica Peters <monica.peters@gfachallenger.gauntletai.com>
#
# Purpose: Multi-stage build using `next.config.ts` output mode `standalone`.
# Usage: Railway service Root Directory = `frontend`, or: docker build -t prd2-dashboard ./frontend
# Dependencies: Node 20+, OpenEMR OAuth/FHIR URLs via env at runtime (not baked in).
# Security/PHI: No secrets in image; pass AUTH_* and OPENEMR_* via Railway Variables.

FROM node:20-alpine AS base
RUN apk add --no-cache libc6-compat
WORKDIR /app

FROM base AS deps
COPY package.json package-lock.json ./
RUN npm ci

FROM base AS builder
COPY --from=deps /app/node_modules ./node_modules
COPY . .
ENV NEXT_TELEMETRY_DISABLED=1
RUN npm run build

FROM base AS runner
ENV NODE_ENV=production
ENV NEXT_TELEMETRY_DISABLED=1
RUN addgroup --system --gid 1001 nodejs \
    && adduser --system --uid 1001 nextjs
COPY --from=builder /app/public ./public
COPY --from=builder --chown=nextjs:nodejs /app/.next/standalone ./
COPY --from=builder --chown=nextjs:nodejs /app/.next/static ./.next/static
USER nextjs
EXPOSE 3000
ENV PORT=3000
ENV HOSTNAME=0.0.0.0
CMD ["node", "server.js"]
