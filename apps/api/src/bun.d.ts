/**
 * The few Bun APIs the server entry point uses (server.ts only; shared packages must not use Bun APIs, see
 * eslint.config.js). Declared here instead of `@types/bun`, whose globals clash with the DOM lib of the
 * single repository tsconfig.
 */
interface BunServer {
  readonly port: number;
  requestIP(req: Request): { address: string; port: number } | null;
  stop(closeActiveConnections?: boolean): Promise<void>;
}

declare const Bun: {
  readonly version: string;
  serve(options: {
    port: number;
    hostname: string;
    maxRequestBodySize?: number;
    fetch(req: Request, server: BunServer): Response | Promise<Response>;
  }): BunServer;
};
