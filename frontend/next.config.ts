import type { NextConfig } from "next";

const nextConfig: NextConfig = {
  // Production Docker / Railway: emit `.next/standalone` (see `frontend/Dockerfile`).
  output: "standalone",
  // Monorepo: OpenEMR repo also has a root `package-lock.json`; pin Turbopack root to this app.
  turbopack: {
    root: process.cwd(),
  },
  // Playwright (and some proxies) may hit dev assets from 127.0.0.1 while Next prints localhost URLs.
  allowedDevOrigins: ["127.0.0.1", "localhost"],
};

export default nextConfig;
